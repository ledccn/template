<?php

namespace Ledc\Template\Model;

/**
 * 模型观察者：global_log
 * @usage GlobalLog::observe(GlobalLogObserver::class);
 */
class GlobalLogObserver
{
   /**
     * 监听数据即将创建的事件。
     *
     * @param GlobalLog $model
     * @return void
     */
    public function creating(GlobalLog $model): void
    {
    }

    /**
     * 监听数据创建后的事件。
     *
     * @param GlobalLog $model
     * @return void
     */
    public function created(GlobalLog $model): void
    {
    }

    /**
     * 监听数据即将更新的事件。
     *
     * @param GlobalLog $model
     * @return void
     */
    public function updating(GlobalLog $model): void
    {
    }

    /**
     * 监听数据更新后的事件。
     *
     * @param GlobalLog $model
     * @return void
     */
    public function updated(GlobalLog $model): void
    {
    }

    /**
     * 监听数据即将保存的事件。
     *
     * @param GlobalLog $model
     * @return void
     */
    public function saving(GlobalLog $model): void
    {
    }

    /**
     * 监听数据保存后的事件。
     *
     * @param GlobalLog $model
     * @return void
     */
    public function saved(GlobalLog $model): void
    {
    }

    /**
     * 监听数据即将删除的事件。
     *
     * @param GlobalLog $model
     * @return void
     */
    public function deleting(GlobalLog $model): void
    {
    }

    /**
     * 监听数据删除后的事件。
     *
     * @param GlobalLog $model
     * @return void
     */
    public function deleted(GlobalLog $model): void
    {
    }

    /**
     * 监听数据即将从软删除状态恢复的事件。
     *
     * @param GlobalLog $model
     * @return void
     */
    public function restoring(GlobalLog $model): void
    {
    }

    /**
     * 监听数据从软删除状态恢复后的事件。
     *
     * @param GlobalLog $model
     * @return void
     */
    public function restored(GlobalLog $model): void
    {
    } 
}
