<?php

namespace Silnik\Utils\Abstracts;

abstract class Strings
{
    public function __construct()
    {
    }
    //limpa tags HTML
    public function ClearTags($var)
    {
        return preg_replace('/<[^>]*>/', ' ', $this->HtmlEntities($var));
    }
    //compacta codigo HTML
    public static function Compactar($b, $bolean)
    {
        if ($bolean == false) {
            return $b;
        }
        ob_start('compactar');
        $b = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $b);
        $b = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $b);

        return $b;
    }

    //transforma Texto para HTML_ENTITIES
    public static function HtmlEntities($string)
    {
        $trans_tbl = get_html_translation_table(HTML_ENTITIES);
        $trans_tbl = array_flip($trans_tbl);

        return strtr($string, $trans_tbl);
    }
    //transforma Texto para UTF-8
    public static function HtmlEntitiesUTF8($string)
    {
        return htmlentities($string, ENT_QUOTES, 'UTF-8');
    }

    public static function UrlAmigavel($string)
    {
        $table = ['Š' => 'S', 'š' => 's', 'Đ' => 'Dj', 'đ' => 'dj', 'Ž' => 'Z', 'ž' => 'z', 'Č' => 'C', 'č' => 'c', 'Ć' => 'C', 'ć' => 'c', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y', 'Ŕ' => 'R', 'ŕ' => 'r'];
        $string = strtr($string, $table);
        $string = strtolower($string);
        $string = preg_replace("/[^a-z0-9_\s-]/", '', $string);
        $string = preg_replace("/[\s-]+/", ' ', $string);
        $string = preg_replace("/[\s_]/", '-', $string);

        return $string;
    }

    //cria um código aleatório conforme solicitado via par�metro
    public static function ShortURL($tamanho = 10, $maiusculas = true, $numeros = true)
    {
        $lmin = 'abcdefghijklmnopqrstuvwxyz';
        $lmai = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $num = '1234567890';
        $retorno = '';
        $caracteres = '';
        $caracteres .= $lmin;
        if ($maiusculas) {
            $caracteres .= $lmai;
        }
        if ($numeros) {
            $caracteres .= $num;
        }
        $len = strlen($caracteres);
        for ($n = 1;$n <= $tamanho;$n++) {
            $rand = mt_rand(1, $len);
            $retorno .= $caracteres[$rand - 1];
        }

        return $retorno;
    }

    //retorna o numero de palavas referente aos número de $chars
    public static function limitText($string, $chars = 50, $ret = '...')
    {
        if (strlen($string) > $chars) {
            while (substr($string, $chars, 1) <> ' ' && ($chars < strlen($string))) {
                $chars++;
            };
        };

        return strip_tags(substr($string, 0, $chars) . $ret);
    }

    /**
     * enc
     *
     * @param string $p password
     * @param boolean $c add sha1
     * @return string
     */
    public static function enc(string $p, $c = false): string
    {
        $p = mb_convert_encoding($p, 'UTF-8', 'ISO-8859-1');
        $d = ';;;';
        $l = strlen((string)$p);
        if ($l > 1) {
            for ($i = 0;$i < $l;$i++) {
                $a = ord($p[$i]);
                $p[$i] = chr($a++);
            }
        } else {
            $a = ord($p);
            $p = chr($a++);
        }$p = strrev($p);
        if ($l % 2 != 0) {
            $p .= $d;
        }$l = strlen($p);
        $di = ($l / 2) - (($l % 2 != 0) ? .5 : 0);
        $p = substr($p, $di, $l) . substr($p, 0, $di);
        $p = base64_encode($p);
        if ($c) {
            $p = sha1($p);
        }

        return $p;
    }

    //captalize
    public function str_captalize($v)
    {
        return ucwords(strtolower($v));
    }
    //lower
    public function str_lower($v)
    {
        return strtolower($v);
    }
    //upper
    public function str_upper($v)
    {
        return strtoupper($v);
    }
    //flower
    public function str_flower($v)
    {
        return ucfirst($v);
    }
}
