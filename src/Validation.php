<?php
/**
 * Created by PhpStorm.
 * User: Mikkel Rasmussen
 * Date: 20-11-2018
 * Time: 10:35
 */

class Validation
{
    private $_inputs,
            $_requirements,
            $_validations = array();

    /**
     * @var array
     */
    protected $validUrlPrefixes = array('http://', 'https://', 'ftp://');

    /**
     * Validation constructor.
     * This should be called like: new Validation($_POST) or new Validation(['password' => $password, 'username' => $username])
     *
     * @param array $array
     */
    public function __construct($array = null)
    {
        if($this->isArray($array))
            $this->_inputs = $array;

        return $this;
    }

    /**
     * This is where you set the requirements for each value in the $inputs array
     * Examples for the call:
     * - $v->requirements([
     * -   'password' => ['required', 'min:8'],
     * -   'username' => 'required'
     * - ]);
     *
     * - $v = new Validation($_POST)->requirements([
     * -   'password' => ['required', 'min:8'],
     * -   'username' => 'required'
     * - ]);
     *
     * @param array $array
     * @return $this
     */
    public function requirements(Array $array)
    {
        $this->_requirements = $array;

        return $this;
    }

    /**
     * Checks if your array with input passes your array with requirements/rules
     *
     * @param bool $debug
     * @return array|bool
     */
    public function validate($debug = false)
    {
        foreach($this->_inputs as $name => $value){
            if(isset($this->_requirements[$name])){
                if($this->isArray($this->_requirements[$name])){
                    foreach($this->_requirements[$name] as $rule){
                        if($this->contains($rule, ':')){
                            $params = explode(':', $rule);
                            if($this->contains($params[1], '$')){
                                $this->_validations[$name][$params[0]] = call_user_func_array(array($this, $params[0]), array($value, $this->_inputs[substr($params[1], 1)]));
                            }elseif($this->contains($params[1], '|')){
                                $params[1] = explode('|', $params[1]);
                                $this->_validations[$name][$params[0]] = call_user_func_array(array($this, $params[0]), array($value, $params[1][0], $params[1][1]));
                            }else{
                                $this->_validations[$name][$params[0]] = call_user_func_array(array($this, $params[0]), array($value, $params[1]));
                            }
                        }else{
                            $this->_validations[$name][$rule] = call_user_func(array($this, $rule), $value);
                        }
                    }
                }else{
                    if($this->contains($this->_requirements[$name], ':')){
                        $params = explode(':', $this->_requirements[$name]);
                        if($this->contains($params[1], '$')){
                            $this->_validations[$name][$params[0]] = call_user_func_array(array($this, $params[0]), array($value, $this->_inputs[substr($params[1], 1)]));
                        }elseif($this->contains($params[1], '|')){
                            $params[1] = explode('|', $params[1]);
                            $this->_validations[$name][$params[0]] = call_user_func_array(array($this, $params[0]), array($value, $params[1][0], $params[1][1]));
                        }else{
                            $this->_validations[$name][$params[0]] = call_user_func_array(array($this, $params[0]), array($value, $params[1]));
                        }
                    }else{
                        $this->_validations[$name][$this->_requirements[$name]] = call_user_func(array($this, $this->_requirements[$name]), $value);
                    }
                }
            }else{
                $this->_validations[$name] = true;
            }

            if(!$this->required($value) && $this->hasRequirement($name, 'optional')){
                $this->_validations[$name] = ['optional' => true];
            }
        }

        if($debug == true){
            return $this->_validations;
        }else{
            foreach($this->_validations as $valid){
                if($this->isArray($valid)){
                    foreach($valid as $rule){
                        if($rule == false)
                            return false;
                    }
                }else{
                    if($valid == false)
                        return false;
                }
            }

            return true;
        }
    }

    /**
     * Checks
     *
     * @param $input
     * @param $requirement
     * @return bool
     */
    public function hasRequirement($input, $requirement){
        if($this->in($requirement, $this->_requirements[$input])){
            return true;
        }

        return false;
    }

    /**
     * This the field is optional it is always true
     *
     * @param $value
     * @return bool
     */
    private function optional($value){
        return true;
    }

    /**
     * Checks if $value is a array
     *
     * @param $value
     * @return bool
     */
    public function isArray($value)
    {
        return is_array($value);
    }

    /**
     * Check if $value is an integer
     *
     * @param $value
     * @param bool $strict
     * @return bool|false|int
     */
    public function isInteger($value, $strict = false)
    {
        if($strict)
            return preg_match('/^([0-9]|-[1-9]|-?[1-9][0-9]*)$/i', $value);

        return filter_var($value, \FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Checks if $value is numeric
     *
     * @param $value
     * @return bool
     */
    public function isNumeric($value)
    {
        return is_numeric($value);
    }

    /**
     * Checks if $value is filled out
     *
     * @param $value
     * @return bool
     */
    public function required($value)
    {
        if(is_null($value)){
            return false;
        }elseif(is_string($value) && trim($value) === ""){
            return false;
        }

        return true;
    }

    /**
     * Checks if $value & $value2 is the same
     *
     * @param $value
     * @param $value2
     * @return bool
     */
    public function equals($value, $value2)
    {
        return $value == $value2;
    }

    /**
     * Checks if $value & $value2 is different
     *
     * @param $value
     * @param $value2
     * @return bool
     */
    public function different($value, $value2)
    {
        return $value != $value2;
    }

    /**
     * Checks if $value is a minimum of $length characters
     *
     * @param $value
     * @param $length
     * @return bool
     */
    public function minLength($value, $length)
    {
        if(!$this->isString($value))
            return false;

        if(function_exists("mb_strlen"))
            return mb_strlen($value) >= $length;

        return strlen($value) >= $length;
    }

    /**
     * Checks if $value is a maximum of $length characters
     *
     * @param $value
     * @param $length
     * @return bool
     */
    public function maxLength($value, $length)
    {
        if(!$this->isString($value))
            return false;

        if(function_exists("mb_strlen"))
            return mb_strlen($value) <= $length;

        return strlen($value) <= $length;
    }

    /**
     * Checks if $value is between $min and $max characters
     *
     * @param $value
     * @param $min
     * @param $max
     * @return bool
     */
    public function lengthBetween($value, $min, $max)
    {
        if(!$this->isString($value))
            return false;

        return $this->minLength($value, $min) && $this->maxLength($value, $max);
    }

    /**
     * Checks if $value is $length characters
     *
     * @param $value
     * @param $length
     * @return bool
     */
    public function length($value, $length)
    {
        if(!$this->isString($value))
            return false;

        if(function_exists("mb_strlen"))
            return mb_strlen($value) == $length;

        return strlen($value) == $length;
    }

    /**
     * Checks if $value is a string
     *
     * @param $value
     * @return bool
     */
    public function isString($value)
    {
        return is_string($value);
    }

    /**
     * Checks if $value is minimum $amount
     *
     * @param $value
     * @param $amount
     * @return bool
     */
    public function min($value, $amount)
    {
        if($this->isNumeric($value))
            return $value >= $amount;

        if($this->isString($value))
            return $this->minLength($value, $amount);

        return false;
    }

    /**
     * Checks if $value is maximum $amount
     *
     * @param $value
     * @param $amount
     * @return bool
     */
    public function max($value, $amount)
    {
        if($this->isNumeric($value))
            return $value <= $amount;

        if($this->isString($value))
            return $this->maxLength($value, $amount);

        return false;
    }

    /**
     * Checks if $value is between $min and $max
     *
     * @param $value
     * @param $min
     * @param $max
     * @return bool
     */
    public function between($value, $min, $max)
    {
        if($this->isNumeric($value))
            return $value >= $min && $value <= $max;

        if($this->isString($value))
            return $this->betweenLength($value, $min, $max);

        return false;
    }

    /**
     * Checks if $value is in $array
     *
     * @param $value
     * @param $array
     * @param bool $strict
     * @return bool
     */
    public function in($value, $array, $strict = false)
    {
        return in_array($value, $array, $strict);
    }

    /**
     * Checks if $value is a valid IP
     *
     * @param $value
     * @return bool
     */
    public function ip($value)
    {
        return filter_var($value, \FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Checks if $value is a valid IPv4
     *
     * @param $value
     * @return bool
     */
    public function ipv4($value)
    {
        return filter_var($value, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) !== false;
    }

    /**
     * Checks if $value is a valid IPv6
     *
     * @param $value
     * @return bool
     */
    public function ipv6($value)
    {
        return filter_var($value, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Checks if $value is a valid email
     *
     * @param $value
     * @return bool
     */
    public function email($value)
    {
        return filter_var($value, \FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Checks if $value is a valid email & if the domain name is active
     *
     * @param $value
     * @return bool
     */
    public function emailDNS($value)
    {
        if($this->email($value)) {
            $domain = ltrim(stristr($value, '@'), '@') . '.';
            if(function_exists('idn_to_acssi') && defined('INTL_IDNA_VARIANT_UTS46')) {
                $domain = idn_to_ascii($domain, 0, INTL_IDNA_VARIANT_UTS46);
            }
            return checkdnsrr($domain, 'ANY');
        }
        return false;
    }

    /**
     * Checks if $value is a valid URL by syntax
     *
     * @param $value
     * @return bool
     */
    public function url($value)
    {
        foreach ($this->validUrlPrefixes as $prefix) {
            if (strpos($value, $prefix) !== false) {
                return filter_var($value, \FILTER_VALIDATE_URL) !== false;
            }
        }

        return false;
    }

    /**
     * Check if $value is an active URL by verifying DNS record
     *
     * @param $value
     * @return bool
     */
    public function urlActive($value)
    {
        foreach ($this->validUrlPrefixes as $prefix) {
            if(strpos($value, $prefix) !== false) {
                $host = parse_url(strtolower($value), PHP_URL_HOST);

                return checkdnsrr($host, 'A') || checkdnsrr($host, 'AAAA') || checkdnsrr($host, 'CNAME');
            }
        }

        return false;
    }

    /**
     * Checks if $value passes a regular expression ($regex) check
     *
     * @param $value
     * @param $regex
     * @return false|int
     */
    public function regex($value, $regex)
    {
        return preg_match($regex, $value);
    }

    /**
     * Checks if $value is a valid date
     *
     * @param $value
     * @return bool
     */
    public function date($value)
    {
        $isDate = false;
        if($value instanceof \DateTime) {
            $isDate = true;
        } else {
            $isDate = strtotime($value) !== false;
        }

        return $isDate;
    }

    /**
     * Checks if $value matches a given date format ($format)
     *
     * @param $value
     * @param $format
     * @return bool
     */
    public function dateFormat($value, $format)
    {
        $parsed = date_parse_from_format($format, $value);

        return $parsed['error_count'] === 0 && $parsed['warnings_count'] === 0;
    }

    /**
     * Checks if $value is before a given date ($param)
     *
     * @param $value
     * @param $param
     * @return bool
     */
    public function dateBefore($value, $param)
    {
        $vtime = ($value instanceof \DateTime) ? $value->getTimestamp() : strtotime($value);
        $ptime = ($param instanceof \DateTime) ? $param->getTimestamp() : strtotime($param);

        return $vtime < $ptime;
    }

    /**
     * Checks if $value is after a given date ($param)
     *
     * @param $value
     * @param $param
     * @return bool
     */
    public function dateAfter($value, $param)
    {
        $vtime = ($value instanceof \DateTime) ? $value->getTimestamp() : strtotime($value);
        $ptime = ($param instanceof \DateTime) ? $param->getTimestamp() : strtotime($param);

        return $vtime > $ptime;
    }

    /**
     * Checks if $value is an boolean
     *
     * @param $value
     * @return bool
     */
    public function isBoolean($value)
    {
        return is_bool($value);
    }

    public function creditcard($value)
    {
        /**
         * Luhn algorithm
         * https://en.wikipedia.org/wiki/Luhn_algorithm
         * Borrowed from https://github.com/vlucas - Who has probably taken it from somewhere else
         *
         * @return bool
         */
        $numberIsValid = function () use ($value) {
            $number = preg_replace('/[^0-9]+/', '', $value);
            $sum = 0;

            $strlen = strlen($number);
            if ($strlen < 13) {
                return false;
            }
            for ($i = 0; $i < $strlen; $i++) {
                $digit = (int)substr($number, $strlen - $i - 1, 1);
                if ($i % 2 == 1) {
                    $sub_total = $digit * 2;
                    if ($sub_total > 9) {
                        $sub_total = ($sub_total - 10) + 1;
                    }
                } else {
                    $sub_total = $digit;
                }
                $sum += $sub_total;
            }
            if ($sum > 0 && $sum % 10 == 0) {
                return true;
            }

            return false;
        };

        if ($numberIsValid()) {
            if (!isset($cards)) {
                return true;
            } else {
                $cardRegex = array(
                    'visa' => '#^4[0-9]{12}(?:[0-9]{3})?$#',
                    'mastercard' => '#^(5[1-5]|2[2-7])[0-9]{14}$#',
                    'amex' => '#^3[47][0-9]{13}$#',
                    'dinersclub' => '#^3(?:0[0-5]|[68][0-9])[0-9]{11}$#',
                    'discover' => '#^6(?:011|5[0-9]{2})[0-9]{12}$#',
                );

                if (isset($cardType)) {
                    // if we don't have any valid cards specified and the card we've been given isn't in our regex array
                    if (!isset($cards) && !in_array($cardType, array_keys($cardRegex))) {
                        return false;
                    }

                    // we only need to test against one card type
                    return (preg_match($cardRegex[$cardType], $value) === 1);

                } elseif (isset($cards)) {
                    // if we have cards, check our users card against only the ones we have
                    foreach ($cards as $card) {
                        if (in_array($card, array_keys($cardRegex))) {
                            // if the card is valid, we want to stop looping
                            if (preg_match($cardRegex[$card], $value) === 1) {
                                return true;
                            }
                        }
                    }
                } else {
                    // loop through every card
                    foreach ($cardRegex as $regex) {
                        // until we find a valid one
                        if (preg_match($regex, $value) === 1) {
                            return true;
                        }
                    }
                }
            }
        }

        // if we've got this far, the card has passed no validation so it's invalid!
        return false;
    }

    /**
     * Checks if $value contains $contains in it
     *
     * @param $value
     * @param $contains
     * @return bool
     */
    public function contains($value, $contains)
    {
        if(!isset($contains))
            return false;

        if(!$this->isString($value) || !$this->isString($contains))
            return false;

        if(function_exists('mb_strpos')) {
            $isContains = mb_strpos($value, $contains) !== false;
        }else{
            $isContains = strpos($value, $contains) !== false;
        }

        return $isContains;
    }

    /**
     * Checks if $values is "accepted" (Based on PHP's string evaluation rules)
     *
     * @param $value
     * @return bool
     */
    public function accepted($value)
    {
        $acceptable = ['yes', 'on', 1, '1', true];

        return $this->required($value) && in_array($value, $acceptable, true);
    }

    /**
     * Checks if $values contains only alpha-numeric characters, dashes and underscores
     *
     * @param $value
     * @return bool|false|int
     */
    public function slug($value)
    {
        if($this->isArray($value))
            return false;

        return preg_match('/^([-a-z-0-9_-])+$/i', $value);
    }

    /**
     * Checks if $value contains only alphabetic characters (a-z)
     *
     * @param $value
     * @return false|int
     */
    public function alpha($value)
    {
        return preg_match('/^([a-z])+$/i', $value);
    }

    /**
     * Checks if $value contains only alpha-numeric characters (a-z & 0-9)
     *
     * @param $value
     * @return false|int
     */
    public function alphaNum($value)
    {
        return preg_match('/^([a-z0-9])+$/i', $value);
    }
}