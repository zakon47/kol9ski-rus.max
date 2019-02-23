<?php

namespace Brocker;

interface BrockerInterface{
    public function init($sync):bool;

    public function getMarket($coin);
    public function getBalance($coin);

}