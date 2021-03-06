<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2021-12-11 00:34:05
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2021-12-27 22:59:22
 */


namespace common\interfaces;

/**
 * Interface PayHandler
 * @package common\interfaces
 */
interface PayHandler
{
    /**
     * 支付说明
     *
     * @return string
     */
    public function getBody(): string;

    /**
     * 支付详情
     *
     * @return string
     */
    public function getDetails(): string;

    /**
     * 支付金额
     *
     * @return float
     */
    public function getTotalFee(): float;

    /**
     * 获取订单号
     *
     * @return float
     */
    public function getOrderSn(): string;

    /**
     * 是否查询订单号(避免重复生成)
     *
     * @return bool
     */
    public function isQueryOrderSn(): bool;
}