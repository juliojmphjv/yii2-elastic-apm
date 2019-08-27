<?php


namespace ivoglent\yii2\apm\listeners;

use ivoglent\yii2\apm\Listener;
use yii\base\ActionEvent;
use yii\base\Event;
use yii\console\Application;

class ConsoleListener extends Listener
{
    public $skipCommands = [];
    public function init()
    {
        parent::init(); // TODO: Change the autogenerated stub
        Event::on(Application::class, Application::EVENT_BEFORE_ACTION, [$this, 'beforeAction']);
        Event::on(Application::class, Application::EVENT_AFTER_ACTION, [$this, 'afterAction']);
    }

    /**
     * @param ActionEvent $event
     */
    public function beforeAction(ActionEvent $event) {
        $txtName = sprintf('%s.%s', $event->action->controller->id, $event->action->id);
        $commandName = str_replace('.', '/', $txtName);
        if (in_array($commandName, $this->skipCommands)) {
            return;
        }
        if ($event->action->controller->module) {
            $txtName = $event->action->controller->module->id . '.' . $txtName;
        }
        \Yii::info('Command start: ' . $txtName, 'apm');
        $this->agent->startTransaction($txtName, 'console');
    }

    /**
     * @param ActionEvent $event
     * @throws \Elastic\Apm\PhpAgent\Exception\RuntimeException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function afterAction(ActionEvent $event) {
        /** @var Application $sender */
        $sender = $event->sender;
        $commandName = sprintf('%s/%s', $event->action->controller->id, $event->action->id);
        if (in_array($commandName, $this->skipCommands)) {
            return;
        }
        $status = $sender->getResponse()->exitStatus;
        $result = $status  === 0 ? 'Success' : 'Failed';
        $result = $result . "({$status})";
        $this->agent->stopTransaction($result);
    }
}