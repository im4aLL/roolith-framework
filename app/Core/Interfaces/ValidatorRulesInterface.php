<?php
namespace App\Core\Interfaces;


interface ValidatorRulesInterface
{
    /**
     * @return $this
     */
    public function isRequired();

    /**
     * @return $this
     */
    public function isEmail();

    /**
     * @param $length int
     * @return $this
     */
    public function minLength($length);

    /**
     * @param $length
     * @return $this
     */
    public function maxLength($length);

    /**
     * @return $this
     */
    public function isArray();

    /**
     * @param $condition
     * @return $this
     */
    public function isRequiredIf($condition);

    /**
     * @param $condition
     * @return $this
     */
    public function notExists($condition);

    /**
     * @return $this
     */
    public function isUrl();

    /**
     * @return $this
     */
    public function isNumeric();

    /**
     * Get rules
     *
     * @return array
     */
    public function rules();
}