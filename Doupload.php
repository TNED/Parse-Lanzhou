<?php
namespace app\lanzhou\controller;

use app\parse\controller\ParseHtml;
use think\facade\Request;
class Doupload extends Lanzhou
{
    private $DouploadUrl="https://up.woozooo.com/doupload.php";
    private $mydiskUrl="https://up.woozooo.com/mydisk.php";
    /**
     * @return mixed|string|\think\response\Json
     */
    public function index(){
        $task =(int) Request::param("task",-1);
        if ($task === -1){
            return json(["code"=>302,"msg"=>"未知的访问！请确认参数是否正确。"]);
        }
        $return = [
            "code"=>302,
            "msg"=>"非法访问,未发现正确的参数！"
        ];
        $cookie = Request::param("cookie",null);
        $url = Request::param("url",null);
        Switch ($task){
            case 2://创建floder
                $floder_name = Request::param("name",false);
                if($floder_name==""){
                    $return['msg'] = "参数不完整。未发现新建的文件夹名称。";
                    return json($return);
                }
                $floder_id = Request::param("floder_id",-1);
                $floder_des = Request::param("des",null);
                $return = $this->CreatFloder("$floder_name","$floder_id","$floder_des","$cookie","$url");
                break;
            case 3:
                $floder_id = Request::param("floder_id",-1);
                $return = $this->DelFloder("$floder_id","$cookie","$url");
                break;
            case 5://获取floder下的文件
                $floder_id = Request::param("floder_id",-1);
                $return = $this->GetFilename("$floder_id","$cookie","$url");
                break;
            case 6://删除文件
                $file_id = Request::param("file_id",false);
                $return = $this->DelFile("$file_id","$cookie","$url");
                break;
            case 16:
                $floder_id = Request::param("floder_id",-1);
                $password = Request::param("password",null);
                $return = $this->SetFloderPasswd("$floder_id","$password","$password","url");
                break;
            case 18:
                $floder_id = Request::param("floder_id",-1);
                $disk_url = Request::param("disk_url","");
                $return = $this->GetFloder("$floder_id","$cookie","$url","$disk_url");
                break;
            case 22:
                $file_id = Request::param("file_id",false);
                $password = Request::param("password",null);
                $return = $this->SetFilePasswd($file_id,"$password","$cookie","$url");
                break;
        }
        return json($return);
    }

    /** 提取蓝奏云的数据 task 18
     * @param int $folder_id
     * @param string $cookie
     * @param string $url
     * @param string $passwdurl
     * @return array
     */
    protected function GetFloder($folder_id=-1,$cookie="",$url="",$passwdurl=""){
        $getdata="action=index&folder_id=$folder_id&folder_node=1&item=files";
        if ($cookie==""){$cookie=$this->GetCookie();}
        if($url==""){$url=$this->mydiskUrl;}
        if($passwdurl==""){$passwdurl=$this->DouploadUrl;}
        $arr_re=[
            "code"=>200,
            "msg"=>"提取成功",
            "count"=>-1,
            "data"=>[
            ]
        ];
        $content=$this->curl_request($url."?".$getdata,"",$cookie,"");
        $dom =new ParseHtml($content);
        $div = $dom->findBreadthFirst('#sub_folder_list div.f_tb');//->getPlainText()取得文件的名称
        foreach ($div as $k=>$v){
            try{
                $a2=$div[$k]->findBreadthFirst("a",2);
                $id=$a2->getAttr("tabindex");
            }catch (\Exception $e){
                $arr_re["data"][]="数据提取失败！";
                continue;
            }
            $json=$this->curl_request($passwdurl,[
                "folder_id"=>$id,
                "task"=>18
            ],$cookie,"");
            $data=json_decode($json,true);
            $password=null;
            if($data["info"]["onof"]==="1"){
                $password=$data["info"]["pwd"];
            }
            $arr=[
                "id"=>$id,
                "name"=>$data["info"]["name"],
                "password"=>$password,
                "notes"=>$data["info"]["des"],
                "url"=>$data["info"]["is_newd"]."/b".$id
            ];
            $arr_re["data"][]=$arr;
        }
        $arr_re["count"]=count($arr_re["data"]);
        $arr_re["count"]=count($arr_re["data"]);
        return $arr_re;
    }

    /**
     * 创建文件夹 task 2
     * @param $name
     * @param int $pid
     * @param string $des
     * @param string $cookie
     * @param string $url
     * @return mixed
     */
    protected function CreatFloder($name,$pid=0,$des="",$cookie="",$url=""){
        $pid=(int) $pid;
        if ($cookie==""){$cookie=$this->GetCookie();}
        if($url==""){$url=$this->DouploadUrl;}
        $postdata=[
            'folder_name'=>$name,
            'parent_id'=>$pid,
            'folder_description'=>$des,
            'task'=>2
        ];
        $z=$this->curl_request($url,$postdata,$cookie,"");
        $data=json_decode($z,true);
        return $data;
    }

    /**
     * 删除文件夹 task的值为3
     * @param $floder_id
     * @param string $cookie
     * @param string $url
     * @return mixed
     */
    protected function DelFloder($floder_id,$cookie="",$url=""){
        $floder_id=(int) $floder_id;
        if ($cookie==""){$cookie=$this->GetCookie();}
        if($url==""){$url=$this->DouploadUrl;}
        $postdata=[
            'folder_id'=>$floder_id,
            'task'=>3
        ];
        $z=$this->curl_request($url,$postdata,$cookie,"");
        $data=json_decode($z,true);
        return $data;
    }

    /**
     * 设置floder目录访问密码 task的值为16
     * @param $folder_id
     * @param string $passwd
     * @param string $cookie
     * @param string $url
     * @return mixed
     */
    protected function SetFloderPasswd($folder_id,$passwd="",$cookie="",$url=""){
        $folder_id=(int) $folder_id;
        if ($cookie==""){$cookie=$this->GetCookie();}
        if($url==""){$url=$this->DouploadUrl;}
        $show = 1;
        if($passwd==""){$show=0;$passwd=mt_rand(1000,9999);}
        $postdata=[
            'folder_id'=>$folder_id,
            'shownames'=>$passwd,
            'shows'=>$show,
            'task'=>16
        ];
        $z=$this->curl_request($url,$postdata,$cookie,"");
        $data=json_decode($z,true);
        return $data;
    }

    /**
     * 获取文件在floder下的名称 task的值为5
     * @param int $folder_id
     * @param string $cookie
     * @param string $url
     * @return mixed|string
     */
    protected function GetFilename($folder_id = -1,$cookie = "",$url = ""){
        if($url==""){$url=$this->DouploadUrl;}
        if ($cookie==""){$cookie=$this->GetCookie();}
        $postarr=[
            'folder_id'=>$folder_id,
            'pg'=>	1,
            'task'=>5,];
        $v=$this->curl_request($url,$postarr,$cookie,"");
        return $v;
    }

    /**
     * 设置文件密码 task 22
     * @param $file_id
     * @param string $passwd
     * @param string $cookie
     * @param string $url
     * @return mixed|string
     */
    protected function SetFilePasswd($file_id,$passwd="",$cookie="",$url=""){
        if($url==""){$url=$this->DouploadUrl;}
        if ($cookie==""){$cookie=$this->GetCookie();}
        //这个是查询的
        $postarr=[
            'file_id'=>$file_id,
            'task'=>22

        ];
        $onof=1;
        //没有设置密码
        if ($passwd==""){$onof=0;$passwd=mt_rand(1000,9999);}
        //这个是读取的
        $postarr=[
            'file_id'=>$file_id,
            'shownames'=>	$passwd,
            'shows'=>$onof,
            'task'=>23
        ];
        $v=$this->curl_request($url,$postarr,$cookie,"");
        $v =json_decode($v);
        return $v;
    }

    /**
     * 删除文件 task的值为6
     * @param $file_id
     * @param string $passwd
     * @param string $cookie
     * @param string $url
     * @return mixed|string
     */
    protected function DelFile($file_id,$cookie="",$url=""){
        if($url==""){$url=$this->DouploadUrl;}
        if ($cookie==""){$cookie=$this->GetCookie();}
        //这个是查询的
        $postarr=[
            'file_id'=>$file_id,
            'task'=>6
        ];
        $v=$this->curl_request($url,$postarr,$cookie,"");
        $v =json_decode($v);
        return $v;
    }



}