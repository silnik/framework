<?php

namespace Silnik\Utils\Abstracts;

use Money\Money;

abstract class Monetary
{
    public $money;

    public function __construct($number, $moeda = 'BRL')
    {
        $number = ltrim(str_replace(['.', ','], '', $number), '0');
    }

    public function add($money)
    {
        $this->setMoney(($this->getMoney())->add($money->getMoney())->getAmount());

        return $this;
    }

    public function subtract($money)
    {
        $this->setMoney(($this->getMoney())->subtract($money->getMoney())->getAmount());

        return $this;
    }

    public function multiply($money)
    {
        $this->setMoney(($this->getMoney())->multiply($money)->getAmount());

        return $this;
    }

    public function divide($parcela)
    {
        $this->setMoney(($this->getMoney())->divide($parcela)->getAmount());

        return $this;
    }

    public function symmetrical()
    {
        $this->setMoney(($this->getMoney())->multiply(-1)->getAmount());

        return $this;
    }

    public function allocateTo($parcela)
    {
        $parcelas = ($this->getMoney())->allocateTo($parcela);
        $parcelasMonetary = [];
        if (count($parcela) > 0) {
            foreach ($parcelas as $key => $value) {
                $parcelasMonetary[] = ltrim(str_replace(['.', ','], '', $value->getAmount()), '0');
            }
        }

        return $parcelasMonetary;
    }

    public function percentage($pct)
    {
        $money = round($this->getMoney()->getAmount() + (($this->getMoney()->getAmount() / 100) * ((float)$pct)));
        $money = $this->setMoney($money);

        return $this->getMoney();
    }

    public function moneyDb()
    {
        if (!empty($this->money)) {
            $number = ($this->getMoney()->getAmount());
        } else {
            $number = $this->money;
        }

        $numberTmp = str_replace('-', '', $number);
        if (strlen($numberTmp) >= 2) {
            $number = substr($number, 0, -2) . '.' . str_pad(substr($number, -2), 2, '0', STR_PAD_LEFT);
        } else {
            $number = substr($number, 0, -1) . '.' . str_pad(substr($number, -1), 2, '0', STR_PAD_LEFT);
        }

        return number_format($number, 2, '.', '');
    }

    public function moneyBR($type = '')
    {
        if (!empty($this->money)) {
            $number = ($this->moneyDb());
        } else {
            $number = $this->money;
        }

        if ($type == '+contabil') {
            $ret = 'R$ <span class="float-right">' . number_format($number, 2, ',', '.') . '</span>';
        } elseif ($type == '-contabil') {
            $ret = '<span class="text-danger">R$ <span class="float-right">(' . number_format($number, 2, ',', '.') . ')</span></span>';
        } else {
            $ret = 'R$ ' . number_format($number, 2, ',', '.');
        }

        return $ret;
    }

    public function moneyUS()
    {
        $number = ($this->moneyDb());

        return '$ ' . number_format($number, 2, '.', ',');
    }


    /**
     * @return mixed
     */
    public function getMoney()
    {
        return $this->money;
    }

    /**
     * @param mixed $money
     *
     * @return self
     */
    public function setMoney($number, $moeda = 'BRL')
    {
        if ($moeda == 'BRL') {
            $this->money = Money::BRL($number);
        } elseif ($moeda == 'USD') {
            $this->money = Money::USD($number);
        }

        return $this;
    }
}
