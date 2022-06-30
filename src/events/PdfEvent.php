<?php
namespace verbb\giftvoucher\events;

use craft\commerce\elements\Order;

use yii\base\Event;

class PdfEvent extends Event
{
    public ?Order $order;
    public ?string $option = null;
    public ?string $template = null;
    public array $variables = [];
    public mixed $pdf = null;
}
