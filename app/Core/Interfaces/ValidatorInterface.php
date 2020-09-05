<?php
namespace App\Core\Interfaces;


interface ValidatorInterface
{
    /**
     * Check input validity by rules
     *
     * @param $inputs
     * @param $rules
     * @return $this
     */
    public function check($inputs, $rules);

    /**
     * Whether request is valid or not
     *
     * @return bool
     */
    public function success();

    /**
     * Whether request fails in validity check
     *
     * @return bool
     */
    public function fails();

    /**
     * Get all errors after validation check
     *
     * @return iterable
     */
    public function errors();
}