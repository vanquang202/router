<?php

namespace Illuminate\Route;

use Exception;

class Route
{

    /**  
     * Illuminate\Route\Route
     * 
     * private $urlPrefix; 
     * private $flagGruopData; 
     * private $flag; 
     * private $render; 
     */

    private $urlPrefix;
    private $flagGruopData = false;
    private $flag = false;
    private $render;

    /**
     * Middleware request 
     */
    public function middle_request(string $method): void
    {
        if (!($_SERVER['REQUEST_METHOD'] == $method)) throw new Exception("Error method \n Method support $method");
    }

    public function check_query_string($url)
    {



        $arrKey = [];
        $arrValue = [];
        $countUrlHas = 0;
        $listUrlGlobal = explode('/', $GLOBALS['url']);

        $listUrlSent = explode('/', $url);
        if (count($listUrlSent) != count($listUrlGlobal)) return false;

        foreach ($listUrlSent as $k => $a) {
            if (count(explode('{', $a)) > 1) {
                $a = str_replace('{', '', $a);
                $a = str_replace('}', '', $a);
                array_push($arrValue, $listUrlGlobal[$k]);
            } else {
                $countUrlHas++;
                if ($a == $listUrlGlobal[$k]) {
                    array_push($arrKey, $k);
                }
            }
        }

        if ($countUrlHas == count($arrKey)) {
            return $arrValue;
        } else {
            return false;
        }
    }

    /** 
     * Get route
     * @param string $url
     * @param callable|array $mixed
     * @return void
     */
    public static function get($url, $mixed)
    {

        $_this = new static();

        $_this->flag = false;
        if (request('gruop') == 1) {
            if ($data = $_this->check_query_string(request('prefix') . ($url == '/' ? '' : '/' . $url))) {
            } else {
                if ($GLOBALS['url'] != request('prefix') . ($url == '/' ? '' : '/' . $url)) return  $_this->close();
            }
            // if ($GLOBALS['url'] != request('prefix') . ($url == '/' ? '' : '/' . $url)) return  $_this->close();
        } else {
            if ($data = $_this->check_query_string($url)) {
            } else {
                if ($GLOBALS['url'] != $url) return $_this->close();
            }
            // if ($GLOBALS['url'] != $url) return $_this->close();

        }
        $_this->middle_request('GET');


        return $_this->dispatch($url, $mixed, $data ?? null);
    }



    /** 
     * Post route
     * @param string $url
     * @param callable|array $mixed
     * @return void
     */
    public static function post($url, $mixed)
    {
        $_this = new static;

        $_this->flag = false;

        // if (request('gruop')) { 
        //     if ($GLOBALS['url'] != request('prefix') . "/$url") return  $_this->close();
        // } else {
        //     if ($GLOBALS['url'] != $url) return  $_this->close();
        // }
        if (request('gruop') == 1) {
            if ($data = $_this->check_query_string(request('prefix') . ($url == '/' ? '' : '/' . $url))) {
            } else {
                if ($GLOBALS['url'] != request('prefix') . ($url == '/' ? '' : '/' . $url)) return  $_this->close();
            }
            // if ($GLOBALS['url'] != request('prefix') . ($url == '/' ? '' : '/' . $url)) return  $_this->close();
        } else {
            if ($data = $_this->check_query_string($url)) {
            } else {
                if ($GLOBALS['url'] != $url) return $_this->close();
            }
            // if ($GLOBALS['url'] != $url) return $_this->close();

        }
        $_SESSION['post'] = $_POST;

        $_this->middle_request('POST');

        return $_this->dispatch($url, $mixed, $data ?? null);
    }

    /** 
     * Dispath route
     * @param string $url
     * @param callable|array $mixed
     * @return void
     */
    public function dispatch(string $url, callable|array $mixed, array $data = null)
    {
        // if ($GLOBALS['url'] == $url) {

        try {
            $this->flag = true;


            if (is_callable($mixed)) {
                ob_start();
                if ($data != null) {
                    call_user_func_array($mixed, [...$data]);
                } else {
                    call_user_func($mixed);
                }
                $this->render = ob_get_clean();
                return $this;
            };
            $str = '\\' . $mixed[0];
            $method = $mixed[1];

            $_SESSION['url-to'] = $url;

            // (new $str())->$method();
            ob_start();
            if ($data != null) {

                call_user_func_array([new $str(), $method], [...$data]);
            } else {

                call_user_func([new $str(), $method]);
            }
            $this->render = ob_get_clean();

            $_SESSION['url-from'] = $GLOBALS['url'];


            return $this;
        } catch (\Throwable $th) {
            return redirect('error');
        }
        die;

        // } else {
        //     $this->flag = false;
        //     return $this;
        // };
    }


    /**
     * Middleware route
     */
    public function middleware(string $variable = '')
    {

        if (!$this->flag) return;

        $arrays_middleware =  $GLOBALS['KENNER']->register();
        if (!array_key_exists($variable, $arrays_middleware)) return;

        if (!(new $arrays_middleware[$variable]())->middleware()) {
            ob_start();
            echo '<h1>Page not found</h1>';
            $this->render = ob_get_clean();
        };
    }

    /**
     * Gruop route
     */
    public static function gruop(string $prefix, callable $callback)
    {

        $_this = new static();
        $_this->urlPrefix = $prefix;
        $_this->flagGruopData = true;

        $_SESSION['SERVER']['PREFIX'] = $prefix;
        $_SESSION['SERVER']['GRUOP'] = true;
        if (is_callable($callback)) {

            $callback();
            $_SESSION['SERVER']['PREFIX'] = null;
            $_SESSION['SERVER']['GRUOP'] = false;
            // call_user_func($callback);
        }
    }

    /**
     * Resource route
     */
    public static function resource(string $url, string $controller_address)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') return;
        if ($_SERVER['REQUEST_METHOD'] == 'GET') return;
        if ($_SERVER['REQUEST_METHOD'] == 'DELETE') return;
        if ($_SERVER['REQUEST_METHOD'] == 'PUT') return;
    }

    public function close()
    {
        return $this;
    }

    public function __destruct()
    {

        if (!$this->flag) return;

        echo $this->render;
        return;
    }
}