<?php

namespace Silnik\Utils\Abstracts;

abstract class DateTimeMaker
{
    public $dateOBJ;
    /**
     * A constant representing the number of seconds in a minute, for
     * making code more verbose
     *
     * @var integer
     */
    public const SECONDS_IN_A_MINUTE = 60;
    /**
     * A constant representing the number of seconds in an hour, for making
     * code more verbose
     *
     * @var integer
     */
    public const SECONDS_IN_A_HOUR = 3600;
    public const SECONDS_IN_AN_HOUR = 3600;
    /**
     * A constant representing the number of seconds in a day, for making
     * code more verbose
     *
     * @var integer
     */
    public const SECONDS_IN_A_DAY = 86400;
    /**
     * A constant representing the number of seconds in a week, for making
     * code more verbose
     *
     * @var integer
     */
    public const SECONDS_IN_A_WEEK = 604800;
    /**
     * A constant representing the number of seconds in a month (30 days),
     * for making code more verbose
     *
     * @var integer
     */
    public const SECONDS_IN_A_MONTH = 2592000;
    /**
     * A constant representing the number of seconds in a year (365 days),
     * for making code more verbose
     *
     * @var integer
     */
    public const SECONDS_IN_A_YEAR = 31536000;


    private $date;

    private $timeZone;
    private $monthsBR = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

    private $parts = [
        [31104000, 'ano', 'anos', 'a'],
        [2592000, 'm&ecirc;s', 'meses', 'm'],
        [604800, 'semana', 'semanas', 's'],
        [86400, 'dia', 'dias', 'd'],
        [3600, 'hora', 'horas', 'h'],
        [60, 'minuto', 'minutos', 'min'],
        [1, 'segundo', 'segundos', 'seg'],
    ];

    private $timeZonesBR = [
        'AC' => 'America/Rio_branco',   'AL' => 'America/Maceio',
        'AP' => 'America/Belem',        'AM' => 'America/Manaus',
        'BA' => 'America/Bahia',        'CE' => 'America/Fortaleza',
        'DF' => 'America/Sao_Paulo',    'ES' => 'America/Sao_Paulo',
        'GO' => 'America/Sao_Paulo',    'MA' => 'America/Fortaleza',
        'MT' => 'America/Cuiaba',       'MS' => 'America/Campo_Grande',
        'MG' => 'America/Sao_Paulo',    'PR' => 'America/Sao_Paulo',
        'PB' => 'America/Fortaleza',    'PA' => 'America/Belem',
        'PE' => 'America/Recife',       'PI' => 'America/Fortaleza',
        'RJ' => 'America/Sao_Paulo',    'RN' => 'America/Fortaleza',
        'RS' => 'America/Sao_Paulo',    'RO' => 'America/Porto_Velho',
        'RR' => 'America/Boa_Vista',    'SC' => 'America/Sao_Paulo',
        'SE' => 'America/Maceio',       'SP' => 'America/Sao_Paulo',
        'TO' => 'America/Araguaia',
    ];


    public function __construct($d = 'now', $ufTimeZone = 'SP')
    {
        $this->setTimeZone((isset($this->timeZonesBR[$ufTimeZone]) ? $this->timeZonesBR[$ufTimeZone] : $this->timeZonesBR['SP']));
        $this->setDateOBJ(new \DateTime(str_replace('/', '-', $d)));
        $this->getDateOBJ()->setTimezone(new \DateTimeZone($this->getTimeZone()));
    }

    public function statusLastSee(\DateTime $relativeTo)
    {
        $lastSee = ((new \DateTime)->format('U') - ($relativeTo)->format('U')) / 60;
        if ($lastSee < 15) {
            return 'on';
        } elseif ($lastSee < 180) {
            return 'away';
        } elseif ($lastSee < 740) {
            if ((int)(new \DateTime)->format('H') < 8 || (int)(new \DateTime)->format('H') > 19) {
                return 'sleep';
            } else {
                return 'off';
            }
        }

        return 'invisible';
    }
    public function formattedInterval(\DateTime $relativeTo, $past = false, $minSeconds = 60, $abrev = false)
    {
        if (is_null($relativeTo)) {
            $relativeTo = new \DateTime();
        }
        if ($relativeTo->format('Y') == '-0001') {
            return '-';
        }
        $d = new \DateTime();
        $diff = $d->format('U') - $relativeTo->format('U');
        if ($diff < 0) {
            $past = false;
            $diff *= -1;
        }
        if ($diff > $minSeconds) {
            foreach ($this->parts as $subparts) {
                $n = floor($diff / $subparts[0]);
                if ($n) {
                    if ($abrev == true) {
                        $part = $subparts[3];
                    } else {
                        $part = $subparts[$n > 1 ? 2 : 1];
                    }
                    if ($past) {
                        $part .= ' atr&aacute;s';
                    }

                    return sprintf('%d %s', $n, $part);
                }
            }
        }

        return 'agora';
    }

    /**
     * @return string d/m/y - 14/08/1992
     */
    public function dateBR($s = '/')
    {
        return $this->getDateOBJ()->format('d' . $s . 'm' . $s . 'Y');
    }

    /**
     * @return string Y/m/d - 1992/08/14
     */
    public function dateUS($s = '-')
    {
        return $this->getDateOBJ()->format('Y' . $s . 'm' . $s . 'd');
    }

    /**
     * @return string d/m/Y H:i:s - 14/08/1992 13:45:28
     */
    public function dateTimeBR($d = '/', $h = ':')
    {
        return $this->getDateOBJ()->format('d' . $d . 'm' . $d . 'Y H' . $h . 'i' . $h . 's');
    }

    /**
     * @return string Y-m-d H:i:s - 1992/08/14 13:45:28
     */
    public function dateTimeUS($d = '-', $h = ':')
    {
        return $this->getDateOBJ()->format('Y' . $d . 'm' . $d . 'd H' . $h . 'i' . $h . 's');
    }

    /**
     * @return string 1434966025
     */
    public function timestamp($d = '-', $h = ':')
    {
        return $this->getDateOBJ()->getTimestamp();
    }

    /**
     * @return string
    */
    public function addDay($d)
    {
        return $this->getDateOBJ()->add(new \DateInterval('P' . (int)$d . 'D'));
    }

    /**
     * @return string
    */
    public function addMonth($m)
    {
        return $this->getDateOBJ()->add(new \DateInterval('P' . (int)$m . 'D'));
    }

    /**
     * @return string
    */
    public function addYear($y)
    {
        return $this->getDateOBJ()->add(new \DateInterval('P' . (int)$y . 'Y'));
    }

    /**
     * @return string
    */
    public function addHour($h)
    {
        return $this->getDateOBJ()->add(new \DateInterval('PT' . (int)$h . 'H'));
    }
    /**
     * @return string
    */
    public function addMinut($m)
    {
        return $this->getDateOBJ()->add(new \DateInterval('PT' . (int)$m . 'M'));
    }
    /**
     * @return string
    */
    public function addSecond($s)
    {
        return $this->getDateOBJ()->add(new \DateInterval('PT' . (int)$s . 'S'));
    }

    /**
     * @return string
    */
    public function subDay($d)
    {
        return $this->getDateOBJ()->sub(new \DateInterval('P' . (int)$d . 'D'));
    }

    /**
     * @return string
    */
    public function subMonth($m)
    {
        return $this->getDateOBJ()->sub(new \DateInterval('P' . (int)$m . 'D'));
    }

    /**
     * @return string
    */
    public function subYear($y)
    {
        return $this->getDateOBJ()->sub(new \DateInterval('P' . (int)$y . 'Y'));
    }

    /**
     * @return string
    */
    public function subHour($h)
    {
        return $this->getDateOBJ()->sub(new \DateInterval('PT' . (int)$h . 'H'));
    }
    /**
     * @return string
    */
    public function subMinut($m)
    {
        return $this->getDateOBJ()->sub(new \DateInterval('PT' . (int)$m . 'M'));
    }
    /**
     * @return string
    */
    public function subSecond($s)
    {
        return $this->getDateOBJ()->sub(new \DateInterval('PT' . (int)$s . 'S'));
    }

    /**
     * @return string ex America/Sao_Paulo
     */
    public function timeZone()
    {
        return $this->getDateOBJ()->format('e');
    }

    public function month()
    {
        return $this->getDateOBJ()->format('m');
    }

    public function year()
    {
        return $this->getDateOBJ()->format('y');
    }

    public function day()
    {
        return $this->getDateOBJ()->format('d');
    }

    public function hour()
    {
        return $this->getDateOBJ()->format('hour');
    }

    public function minut()
    {
        return $this->getDateOBJ()->format('i');
    }

    public function second()
    {
        return $this->getDateOBJ()->format('s');
    }

    /**
     * @return mixed
     */
    public function getDateOBJ()
    {
        return $this->dateOBJ;
    }

    /**
     * @param mixed $dateOBJ
     *
     * @return self
     */
    public function setDateOBJ(\DateTime $dateOBJ)
    {
        $this->dateOBJ = $dateOBJ;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTimeZone()
    {
        return $this->timeZone;
    }

    /**
     * @param mixed $timeZone
     *
     * @return self
     */
    public function setTimeZone($timeZone)
    {
        $this->timeZone = $timeZone;

        return $this;
    }

    public function returnMonthBr($length = 0)
    {
        $ret = $this->monthsBR[(int)$this->month()];
        if ($length > 0) {
            $ret = substr($ret, 0, $length);
        }

        return $ret;
    }
}
