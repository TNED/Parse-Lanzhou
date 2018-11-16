<?php

namespace app\lanzhou\controller;


use think\facade\Request;

class Fileup extends Lanzhou
{
    private $FileUpurl="https://up.woozooo.com/fileup.php";
    public function index(){
        $k=($this->fileup("",483200,"",""));
        return json($k);
    }

    /**
     * @param string $filepath
     * @param int $folder_id
     * @param string $cookie
     * @param string $url
     * @return mixed
     */
    protected function fileup($filepath="",$folder_id=-1,$cookie="",$url=""){
        $filepath = "D:\SSR2.0.exe";
        if(!class_exists('\CURLFile')){
            $filepath = "@".$filepath;
        }else{
            $filepath = new \CURLFile($filepath);
        }
        if ($cookie==""){$cookie=$this->GetCookie();}
        if($url==""){$url=$this->FileUpurl;}
        $postdata=[
            'folder_id'=>$folder_id,
            'task'=>1,
            "upload_file"=> $filepath
        ];
        $z=$this->curl_request($url,$postdata,$cookie,"");
        $data=json_decode($z,true);
        return $data;
    }




}