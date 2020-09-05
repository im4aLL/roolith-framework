<?php
namespace App\Core;


use App\Core\Interfaces\ValidatorInterface;

class Validator implements ValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function check($inputs, $rules)
    {
        // TODO: Implement check() method.
    }

    /**
     * @inheritDoc
     */
    public function success()
    {
        // TODO: Implement success() method.
    }

    /**
     * @inheritDoc
     */
    public function fails()
    {
        // TODO: Implement fails() method.
    }

    /**
     * @inheritDoc
     */
    public function errors()
    {
        // TODO: Implement errors() method.
    }
}