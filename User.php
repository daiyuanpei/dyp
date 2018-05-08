<?php

namespace App\Model;

use App\Librarys\ArrayUtil;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Base
{
    use SoftDeletes;

    protected $table = 'tb_user';

    protected $primaryKey = 'id';

    protected $dates = ['deleted_at'];

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['username','real_name','mobile','birthday','id_card_num','auth_status'];


    public static function getUsers($params){
        $default = [
            'where'=>[],
            'order_by'=>[],
            'page'=>[]
        ];
        $params = array_merge($default, $params);
        $where = $params['where'];
        $orderBy = $params['order_by'];
        $page = $params['page'];
        $dataList = User::select(
            'tb_user.id as user_id','tb_user.username','tb_user.real_name','tb_user.mobile','tb_user.birthday','tb_user.id_card_num','tb_user.auth_status'
        )->leftjoin('tb_user_extend','tb_user.id','=','tb_user_extend.user_id')
            ->baseWhere($where)
            ->baseOrderBy($orderBy)
            ->basePage($page)
            ->get()
            ->toArray();
        $totalSize = User::leftjoin('tb_user_extend','tb_user.id','=','tb_user_extend.user_id')
        	->baseWhere($where)
            ->count();
        foreach ($dataList as $key=>$value){
            $value['auth_status_text'] = User::getAuthStatusText($value['auth_status']);
            $value['birthday_text'] = date('Y-m-d',$value['birthday']);
            $dataList[$key] = $value;
        }
        return [
            'data'=>$dataList,
            'page_info'=>self::makePageInfo($page, $totalSize)
        ];
    }

    public static function getAuthStatusText($type){
        switch ($type){
            case 0:
                $text = '未实名认证';
                break;
            case 1:
                $text = '实名认证';
                break;
            case 2:
                $text = '实名认证中';
                break;
            default:
                $text = '未定义';
        }
        return $text;
    }

    public static function add($data){
        return User::create($data);
    }

    public static function updateById($Id, $data){
        return User::where('id', $Id)->update($data);   
    }

    public static function delById($id){
        return User::where('id', $id)->delete();
    }

    //用户简信息
    public static function getUsersWhere($params){
        $default = [
            'where'=>[],
            'order_by'=>[],
            'page'=>[]
        ];
        $params = array_merge($default, $params);
        $where = $params['where'];
        $orderBy = $params['order_by'];
        $page = $params['page'];
        $dataRet = User::select(
            'id as user_id','username'
            )
            ->baseWhere($where)
            ->baseOrderBy($orderBy)
            ->basePage($page)
            ->get()
            ->toArray();
        return $dataRet;
    }

    public static function getUsersPage($params){
        $default = [
            'where'=>[],
            'order_by'=>[],
            'page'=>[]
        ];
        $params = array_merge($default, $params);
        $where = $params['where'];
        $page = $params['page'];
        $totalSize = User::baseWhere($where)->count();
        return User::makePageInfo($page, $totalSize);
    }

}
