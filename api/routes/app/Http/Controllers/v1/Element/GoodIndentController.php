<?php

namespace App\Http\Controllers\v1\Element;

use App\Code;
use App\Http\Requests\v1\SubmitGoodIndentRequest;
use App\Models\v1\Good;
use App\Models\v1\GoodLocation;
use App\Models\v1\User;
use Illuminate\Support\Facades\Redis;
use App\common\RedisLock;
use App\Models\v1\GoodIndent;
use App\Models\v1\GoodIndentCommodity;
use App\Models\v1\GoodSku;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GoodIndentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        GoodIndent::$withoutAppends = false;
        GoodIndentCommodity::$withoutAppends = false;
        $q = GoodIndent::query();
        $q->where('user_id',auth('web')->user()->id);
        if($request->index){
            $q->where('state',$request->index);
        }
        $q->where('is_delete',GoodIndent::GOOD_INDENT_IS_DELETE_NO);
        if($request->search == 1){
            $q->where('created_at','>=',date("Y-m-d 00:00:00",strtotime($request->startTime)))->where('created_at','<=',date("Y-m-d 23:59:59",strtotime($request->endTime)));
        }
        $limit=$request->limit;
        $q->orderBy('state','ASC')->orderBy('updated_at','DESC');
        $paginate=$q->with(['goodsList'=>function($q){
            $q->with(['goodSku']);
        }])->paginate($limit);
        return resReturn(1,$paginate);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param SubmitGoodIndentRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(SubmitGoodIndentRequest $request)
    {
        $redis = Redis::connection('default');
        $lock=RedisLock::lock($redis,'goodIndent');
        if($lock){
            $return=DB::transaction(function ()use($request){
                $GoodIndent=new GoodIndent();
                $GoodIndent->user_id=auth('web')->user()->id;
                $GoodIndent->state = GoodIndent::GOOD_INDENT_STATE_PAY;
                $GoodIndent->carriage = $request->carriage;
                $total = 0;
                foreach ($request->indentCommodity as $indentCommodity){
                    $Good=Good::select('id','is_inventory','inventory','sales')->find($indentCommodity['good_id']);
                    if($Good && $Good->is_inventory == Good::GOOD_IS_INVENTORY_NO){ //拍下减库存
                        if(!$indentCommodity['good_sku_id']){ //非SKU商品
                            if($Good->inventory-$indentCommodity['number']<0){
                                return array('存在库存不足的商品，请重新获取购物车',Code::CODE_PARAMETER_WRONG);
                            }
                            $Good->inventory = $Good->inventory-$indentCommodity['number'];
                            $Good->sales = $Good->sales+$indentCommodity['number'];  //加销量
                            $Good->save();
                        }else{
                            $GoodSku=GoodSku::find($indentCommodity['good_sku_id']);
                            if($GoodSku->inventory-$indentCommodity['number']<0){
                                return array('存在库存不足的SKU商品，请重新获取购物车',Code::CODE_PARAMETER_WRONG);
                            }
                            $GoodSku->inventory = $GoodSku->inventory-$indentCommodity['number'];
                            $GoodSku->save();
                            $Good->sales = $Good->sales+$indentCommodity['number'];  //加销量
                            $Good->save();
                        }
                    }
                    $total+=$indentCommodity['number']*$indentCommodity['price'];
                }
                $GoodIndent->identification = orderNumber();
                $GoodIndent->total = $total + $request->carriage;
                $GoodIndent->remark = $request->remark;
                $GoodIndent->save();
                foreach ($request->indentCommodity as $id=>$indentCommodity){
                    $GoodIndentCommodity=new GoodIndentCommodity();
                    $GoodIndentCommodity->good_indent_id = $GoodIndent->id;
                    $GoodIndentCommodity->good_id = $indentCommodity['good_id'];
                    $GoodIndentCommodity->good_sku_id = $indentCommodity['good_sku_id'];
                    $GoodIndentCommodity->img = $indentCommodity['img'];
                    $GoodIndentCommodity->name = $indentCommodity['name'];
                    $GoodIndentCommodity->price = $indentCommodity['price'];
                    $GoodIndentCommodity->number = $indentCommodity['number'];
                    $GoodIndentCommodity->save();
                }
                $GoodLocation=new GoodLocation();
                $GoodLocation->good_indent_id = $GoodIndent->id;
                $GoodLocation->cellphone = $request->address['cellphone'];
                $GoodLocation->name = $request->address['name'];
                $GoodLocation->location = $request->address['location'];
                $GoodLocation->address = $request->address['address'];
                $GoodLocation->latitude = $request->address['latitude'];
                $GoodLocation->longitude = $request->address['longitude'];
                $GoodLocation->house = $request->address['house'];
                $GoodLocation->save();
                return array(1,$GoodIndent->id);
            }, 5);
            RedisLock::unlock($redis,'goodIndent');
            if($return[0] == 1){
                return resReturn(1,$return[1]);
            }else{
                return resReturn(0,$return[0],$return[1]);
            }
        }else{
            return resReturn(0,'业务繁忙，请稍后再试',Code::CODE_SYSTEM_BUSY);
        }
    }

    // 更新商品库存
    public function gcount(Request $request){
        $return = $request->all();
        foreach ($request->all() as $id=> $all){
            if($all['good_sku_id']){ //sku商品
                $GoodSku=GoodSku::find($all['good_sku_id']);
                if($GoodSku->inventory<$all['number']){ //库存不足时
                    $return[$id]['invalid']= true;  //标记为失效
                }else{
                    $return[$id]['invalid']= false;
                }
            }else{
                $Good=Good::find($all['good_id']);
                if($Good->inventory<$all['number']){
                    $return[$id]['invalid']= true;  //标记为失效
                }else{
                    $return[$id]['invalid']= false;
                }
            }
        }
        return resReturn(1,$return);
    }

    // 订单支付详情
    public function pay($id,Request $request)
    {

        GoodIndentCommodity::$withoutAppends = false;
        GoodIndent::$withoutAppends=false;
        User::$withoutAppends=false;
        $GoodIndent=GoodIndent::with(['goodsList'=>function($q){
            $q->select('good_id','good_indent_id')->with(['good'=>function($q){
                $q->select('name','id');
            }]);
        },'User'=>function($q){
            $q->select('id','money');
        }])->select('id','total','user_id')->find($id);
        return resReturn(1,$GoodIndent);
    }

    // 取消订单
    public function cancel($id)
    {
        $redis = Redis::connection('default');
        $lock=RedisLock::lock($redis,'goodIndent');
        if($lock){
            $return=DB::transaction(function ()use($id){
                $GoodIndent=GoodIndent::with(['goodsList'])->find($id);
                $GoodIndent->state = GoodIndent::GOOD_INDENT_STATE_CANCEL;
                $GoodIndent->save();
                //库存处理
                foreach ($GoodIndent->goodsList as $indentCommodity){
                    $Good=Good::select('id','is_inventory','inventory','sales')->find($indentCommodity['good_id']);
                    if($Good && $Good->is_inventory == Good::GOOD_IS_INVENTORY_NO){ //拍下减库存
                        if(!$indentCommodity['good_sku_id']){ //非SKU商品
                            $Good->inventory = $Good->inventory+$indentCommodity['number'];
                            $Good->sales = $Good->sales-$indentCommodity['number'];  //减销量
                            $Good->save();
                        }else{
                            $GoodSku=GoodSku::find($indentCommodity['good_sku_id']);
                            $GoodSku->inventory = $GoodSku->inventory+$indentCommodity['number'];
                            $GoodSku->save();
                            $Good->sales = $Good->sales-$indentCommodity['number'];  //减销量
                            $Good->save();
                        }
                    }
                }
                return array(1,'成功');
            }, 5);
            RedisLock::unlock($redis,'goodIndent');
            if($return[0] == 1){
                return resReturn(1,$return[1]);
            }else{
                return resReturn(0,$return[0],$return[1]);
            }
        }else{
            return resReturn(0,'业务繁忙，请稍后再试',Code::CODE_SYSTEM_BUSY);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function show($id,Request $request)
    {
        GoodIndentCommodity::$withoutAppends = false;
        GoodSku::$withoutAppends=false;
        GoodIndent::$withoutAppends=false;
        $GoodIndent=GoodIndent::with(['goodsList'=>function($q){
            $q->with(['good'=>function($q){
                $q->with(['resourcesMany','goodSku'=>function($q){
                    $q->with('resources')->where('inventory','>',0);
                }]);
            },'goodSku']);
        },'GoodLocation'])->find($id);
        return resReturn(1,$GoodIndent);
    }

    /**
     * 确认收货
     * @param $id
     * @return string
     */
    public function receipt($id){
        $redis = Redis::connection('default');
        $lock=RedisLock::lock($redis,'goodIndent');
        if($lock){
            $return=DB::transaction(function ()use($id){
                $GoodIndent=GoodIndent::find($id);
                $GoodIndent->state = GoodIndent::GOOD_INDENT_STATE_ACCOMPLISH;
                $GoodIndent->confirm_time = Carbon::now()->toDateTimeString();
                $GoodIndent->save();
                return array(1,'收货成功');
            }, 5);
            RedisLock::unlock($redis,'goodIndent');
            if($return[0] == 1){
                return resReturn(1,$return[1]);
            }else{
                return resReturn(0,$return[0],$return[1]);
            }
        }else{
            return resReturn(0,'业务繁忙，请稍后再试',Code::CODE_SYSTEM_BUSY);
        }
    }

    /**
     * 删除
     * @param $id
     * @return string
     */
    public function destroy($id){
        $redis = Redis::connection('default');
        $lock=RedisLock::lock($redis,'goodIndent');
        if($lock){
            $return=DB::transaction(function ()use($id){
                $GoodIndent=GoodIndent::find($id);
                $GoodIndent->is_delete = GoodIndent::GOOD_INDENT_IS_DELETE_YES;
                $GoodIndent->save();
                return array(1,'删除成功');
            }, 5);
            RedisLock::unlock($redis,'goodIndent');
            if($return[0] == 1){
                return resReturn(1,$return[1]);
            }else{
                return resReturn(0,$return[0],$return[1]);
            }
        }else{
            return resReturn(0,'业务繁忙，请稍后再试',Code::CODE_SYSTEM_BUSY);
        }
    }
}