<?php
/**
 * Created by PhpStorm.
 * User: Malson
 * Date: 2017/11/15
 * Time: 上午10:30
 */

namespace Express;


/**
 * 快递查询的工具类
 *
 * Class Tools
 * @package Express
 */
class Express
{

    // +----------------------------------------------------------------------
    // | 定义
    // +----------------------------------------------------------------------

    // 配置项
    private $config = [
        'timeout'          => 5 ,
        // 连接超市时长
        'displayShowError' => FALSE ,
        // 查看异常内容
    ];

    // 自动获取快递类型的地址
    private $autoNumberUrl = 'http://www.kuaidi100.com/autonumber/autoComNum';
    // 查询快递信息的接口地址
    private $queryUrl = 'http://www.kuaidi100.com/query';

    /**
     * 错误信息
     *
     * @var array
     */
    private $errorMsg = [];

    // +----------------------------------------------------------------------
    // | 绑定
    // +----------------------------------------------------------------------

    /**
     * Tools constructor.
     *
     * @param array $config 配置项
     */
    public function __construct ( $config = [] )
    {
        if ( !empty( $config ) )
        {
            $this->config = array_merge( $this->config , $config );
        }
    }

    // +----------------------------------------------------------------------
    // | 方法
    // +----------------------------------------------------------------------

    /**
     * 执行查询
     *
     * @param string $order 快递单号
     *
     * @return array|bool
     */
    public function query ( $order )
    {
        return $this->getExpressInfo( $order );
    }

    /**
     * 获取 错误信息
     *
     * @return array
     */
    public function getErrorMsg ()
    {
        return $this->errorMsg;
    }

    /**
     * 发送curl请求 调取接口
     *
     * @param string $url  接口地址
     * @param array  $data 发送的数据
     * @param bool   $post 是否为post请求,默认为是
     *
     * @return bool|mixed
     */
    private function curl ( $url , $data = [] , $post = TRUE )
    {
        $headers = [
            'Content-Type: application/json' ,
        ];
        $curl    = curl_init();
        if ( $post )
        {
            curl_setopt( $curl , CURLOPT_POST , TRUE );
            curl_setopt( $curl , CURLOPT_POSTFIELDS , $data );
            curl_setopt( $curl , CURLOPT_URL , $url );
        }
        else
        {
            if ( !empty( $data ) )
            {
                // 构建get请求的参数
                $_url_data = http_build_query( $data );
                curl_setopt( $curl , CURLOPT_URL , $url . '?' . $_url_data );
            }
        }

        curl_setopt( $curl , CURLOPT_HEADER , FALSE );
        curl_setopt( $curl , CURLOPT_RETURNTRANSFER , TRUE );
        curl_setopt( $curl , CURLOPT_TIMEOUT , $this->config['timeout'] );
        curl_setopt( $curl , CURLOPT_HTTPHEADER , $headers );


        $resp = curl_exec( $curl );
        if ( $this->config['displayShowError'] )
        {
            // 查看异常内容。
            var_dump( curl_error( $curl ) );
        }
        $info = curl_getinfo( $curl );
        curl_close( $curl );

        if ( isset( $info['http_code'] ) && $info['http_code'] == 200 )
        {
            return $resp;
        }

        return FALSE;

    }

    /**
     * 查询 快递公司
     *
     * @param string $order 快递单号
     *
     * @return bool
     */
    private function getExpressType ( $order )
    {
        $_data = [
            'resultv2' => 1 ,
            'text'     => $order ,
        ];

        if ( $_result = $this->curl( $this->autoNumberUrl , $_data , FALSE ) )
        {
            return json_decode( $_result , TRUE )['auto'][0]['comCode'];
        }
        else
        {
            $this->errorMsg = [
                'code' => '0001' ,
                'msg'  => '请求快递类型时发生错误' ,
            ];

            return FALSE;
        }
    }

    /**
     * 查询 快递信息
     *
     * @param string $order 快递单号
     *
     * @return array|bool
     */
    private function getExpressInfo ( $order )
    {
        // 自动获取快递类型
        if ( !$_expressType = $this->getExpressType( $order ) )
        {
            return FALSE;
        }

        // 构建参数
        $_data = [
            'type'   => $_expressType ,
            'postid' => $order ,
        ];

        // 获取快递信息
        if ( !$_result = $this->curl( $this->queryUrl , $_data , FALSE ) )
        {
            $this->errorMsg = [
                'code' => '0002' ,
                'nsg'  => '请求快递信息时发生错误' ,
            ];

            return FALSE;
        }

        $_expressInfo = json_decode( $_result , TRUE );

        $_express = [
            // 是否签收
            'ischeck' => FALSE ,
            // 快递数据
            'data'    => [] ,
            // 快递单号
            'no'      => '' ,
        ];

        if ( $_expressInfo['message'] === 'ok' )
        {
            $_express['data']    = $_expressInfo['data'];
            $_express['no']      = $_expressInfo['nu'];
            $_express['ischeck'] = $_expressInfo['ischeck'] ? TRUE : FALSE;
        }
        else
        {

            $this->errorMsg = [
                'code' => '0003' ,
                'msg'  => $_expressInfo['message'] ,
            ];

            return FALSE;
        }

        return $_express;

    }

}