<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/1
 * Time: 16:46
 */

namespace app\admin\behavior;
use Firebase\JWT\JWT;

class Auth {

    public function run(){
//        $this->verifyToken();
    }

    public function verifyToken(){
        $token=input('param.token');
        $jwt=new JWT();
        $user=array();
        try{
            $user=$jwt->decode($token,config('jwt_secret'),config('jwt_allowed_algs'));
        }
        catch (\Exception $e){
            echo  json_encode(retmsg(-2,null,$e->getMessage()));exit();
        }
       /* catch (SignatureInvalidException $e){
            echo json(retmsg(-2,null,$e->toString()));exit();
        }
        catch (BeforeValidException $e){
            echo json(retmsg(-2,null,$e->toString()));exit();
        }catch (BeforeValidException $e){
            echo json(retmsg(-2,null,$e->toString()));exit();
        }
        catch (ExpiredException $e){
            echo json(retmsg(-2,null,$e->toString()));exit();
        }*/
        $user = json_decode(json_encode($user),true);
        $GLOBALS['user']=$user;
    }
}
