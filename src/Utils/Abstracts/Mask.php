<?php

namespace Silnik\Utils\Abstracts;

abstract class Mask
{
    public const TELEFONE = '8 OU 9 DIGITOS';
    public const DOCUMENTO = 'CPF OU CNPJ';
    public const CPF = '###.###.###-##';
    public const CNPJ = '##.###.###/####-##';
    public const CEP = '##.###-###';
    public const MAC = '##:##:##:##:##:##';

    /**
     * Adiciona máscara em um texto
     *
     * @param  string   $txt Texto
     * @param  Mask     $mascara
     * @return string (Texto com mascara)
     */
    public static function mask($txt = '', string $mascara = '')
    {
        if (empty($txt) || empty($mascara)) {
            return false;
        } elseif ($mascara == self::TELEFONE) {
            $mascara = (strlen($txt) == 10 ? '(##)####-####' : '(##)#####-####');
        } elseif ($mascara == self::DOCUMENTO) {
            $mascara = (strlen($txt) == 14 ? Mask::CNPJ : (strlen($txt) == 11 ? Mask::CPF : ''));
        }

        return Mask::MaskFactory($txt, $mascara);
    }

    /**
     * Adiciona máscara
     *
     * @param  string $texto
     * @return string (Texto com a mascara)
    */
    public static function maskFactory($txt = '', $mascara = '')
    {
        $txt = Mask::unmask($txt);
        if (empty($txt) || empty($mascara)) {
            return false;
        }

        $qtd = substr_count($mascara, '#');
        if ($qtd != strlen($txt) && strlen($txt) != 0) {
            return false;
        } else {
            $string = str_replace(' ', '', $txt);
            for ($i = 0; $i < strlen($string); $i++) {
                $pos = strpos($mascara, '#');
                $mascara[$pos] = $string[$i];
            }

            return $mascara;
        }
    }


    /**
     * Remove máscara de um texto
     *
     * @param  string $texto
     * @return string (Texto sem a mascara)
     */
    public static function unMask($texto)
    {
        return preg_replace('/[\-\|\(\)\/\.\: ]/', '', $texto);
    }
}
