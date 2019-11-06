<?php

class Validation
{
    /**
     * Generic error messages
     *
     * @var array
     */
    private $_messages = [
        'isArray' => '\':input\' is not an array.',
        'isInteger' => '\':input\' is not an integer.',
        'isNumeric' => '\':input\' is not a numeric string.',
        'required' => '\':input\' is required.',
        'equals' => '\':input\' is not equal to \':param1\'.',
        'different' => '\':input\' is not different from :param1.',
        'isString' => '\':input\' is not a string.',
        'length' => '\':input\' is either too long or short. (should be: :param1 characters)',
        'min' => '\':input\' is too small. (min: :param1)',
        'max' => '\':input\' is too big. (max: :param1)',
        'between' => '\':input\' must be between :param1 and :param2',
        'in' => '\':value is not in \':input\'',
        'ip' => '\':input\' is not a valid IP.',
        'ipv4' => '\':input\' is not a valid IPv4.',
        'ipv6' => '\':input\' is not a valid IPv6.',
        'email' => '\':input\' is not a valid e-mail address.',
        'emailDNS' => '\':input\' is not an active e-mail address.',
        'url' => '\':input\' is not a valid URL. (Valid prefixes: :prefixes)',
        'urlActive' => '\':input\' is not an active URL.',
        'regex' => '\':input\' had no matches with \':param1\'.',
        'date' => '\':input\' is not a valid date.',
        'dateFormat' => '\':input\' must match the format \':param1:\'.',
        'dateBefore' => '\':input\' must be date before \':param1\'.',
        'dateAfter' => '\':input\' must be date after \':param1\'.',
        'isBoolean' => '\':input\' must be a valid boolean.',
        'contains' => '\':input\' does not contain \':param1\'.',
        'accepted' => '\':input\' must be accepted.',
        'slug' => '\':input\' must only contain alpha-numeric characters, dashes and underscores. (a-z & 0-9 & - & _)',
        'alpha' => '\':input\' must only contain alphabetic characters. (a-z)',
        'alphaNum' => '\':input\' must only contain alpha-numeric characters. (a-z & 0-9)',
    ];

    /**
     * @var array $_inputs, $_requirements & $_validations
     */
    private $_inputs = array(),
        $_requirements = array(),
        $_validations = array(),
        $_errors = array(),
        $_errormode = true;

    /**
     * @var array $validUrlPrefixes
     */
    protected $validUrlPrefixes = array('http://', 'https://', 'ftp://');

    /**
     * Validation constructor.
     *
     * Examples on how to call this class:
     * - $v = new Validation($_POST|['username' => $username, 'password' => $password])->requirements([
     * -   'username' => 'required',
     * -   'password' => ['required', 'min:8']
     * - ]);
     * - $passed = $v->validate();
     *
     * - $v = new Validation()->inputs($_POST|['username' => $username, 'password' => $password])->requirements([
     * -   'username' => 'required',
     * -   'password' => ['required', 'min:8']
     * - ]);
     * - $passed = $v->validate();
     *
     * - $v = new Validation($_POST|['username' => $username, 'password' => $password], $rules|['username' => 'required', 'password' => ['required', 'min:8']]);
     * - $passed = $v->validate();
     *
     * - $passed = new Validation($_POST|['username' => $username, 'password' => $password], $rules|['username' => 'required', 'password' => ['required', 'min:8']], true);
     *
     * @param array $inputs
     * @param array $requirements
     * @param bool $validate
     *
     * @return $this|array|bool
     */
    public function __construct($inputs = null, $requirements = null, $validate = false)
    {
        if($this->isArray($inputs))
            $this->_inputs = $inputs;

        if($this->isArray($requirements) && $this->required($requirements))
            $this->_requirements = $requirements;

        if($this->accepted($validate))
            return $this->validate();

        return $this;
    }

    /**
     * Override a error message
     *
     * @param $rule
     * @param null $message
     * @return string
     */
    public function setMessage($rule, $message)
    {
        $this->_messages[$rule] = $message;
    }

    /**
     * Override multiple error messages
     *
     * @param array $array
     * @return string
     */
    public function setMessages(Array $array)
    {
        foreach ($array as $rule => $message) {
            $this->_messages[$rule] = $message;
        }
    }

    /**
     * @param bool $errormode
     */
    public function setErrormode(bool $errormode)
    {
        $this->_errormode = $errormode;
    }

    /**
     * This is where you set the inputs if you didn't do so within $this->__construct()
     *
     * @param array $array
     * @return $this
     */
    public function inputs(Array $array)
    {
        $this->_inputs = $array;

        return $this;
    }

    /**
     * This is where you set the requirements for each value in the $inputs array
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
     * @return array|bool
     */
    public function validate()
    {
        if(!$this->required($this->_inputs) && !$this->required($this->_requirements)){
            if ($this->_errormode) {
                return 'You are missing a set of either/both inputs or/and requirements.';
            } else {
                return false;
            }
        }

        foreach($this->_inputs as $name => $value){
            if(isset($this->_requirements[$name])){
                if($this->isArray($this->_requirements[$name])){
                    foreach($this->_requirements[$name] as $rule){
                        if($this->contains($rule, ':')){
                            $params = explode(':', $rule);
                            if($this->contains($params[1], '$')){
                                if (call_user_func_array(array($this, $params[0]), array($value, $this->_inputs[substr($params[1], 1)])) == false) {
                                    $this->_validations[$name][$params[0]] = false;
                                    $this->_errors[$name][$params[0]] = array($value, substr($params[1], 1), $this->_inputs[substr($params[1], 1)]);
                                } else
                                    $this->_validations[$name][$params[0]] = true;
                            }elseif($this->contains($params[1], '|')){
                                $params[1] = explode('|', $params[1]);
                                if (call_user_func_array(array($this, $params[0]), array($value, $params[1][0], $params[1][1])) == false) {
                                    $this->_validations[$name][$params[0]] = false;
                                    $this->_errors[$name][$params[0]] = array($value, $params[1][0], $params[1][1]);
                                } else
                                    $this->_validations[$name][$params[0]] = true;
                            }else{
                                if (call_user_func_array(array($this, $params[0]), array($value, $params[1])) == false) {
                                    $this->_validations[$name][$params[0]] = false;
                                    $this->_errors[$name][$params[0]] = array($value, $params[1]);
                                }   else
                                    $this->_validations[$name][$params[0]] = true;
                            }
                        }else{
                            if (call_user_func(array($this, $rule), $value) == false) {
                                $this->_validations[$name][$rule] = false;
                                $this->_errors[$name][$rule] = array($value);
                            } else
                                $this->_validations[$name][$rule] = true;
                        }
                    }
                }else{
                    if($this->contains($this->_requirements[$name], ':')){
                        $params = explode(':', $this->_requirements[$name]);
                        if($this->contains($params[1], '$')){
                            if (call_user_func_array(array($this, $params[0]), array($value, $this->_inputs[substr($params[1], 1)])) == false) {
                                $this->_validations[$name][$params[0]] = false;
                                $this->_errors[$name][$params[0]] = array($value, substr($params[1], 1), $this->_inputs[substr($params[1], 1)]);
                            } else
                                $this->_validations[$name][$params[0]] = true;
                        }elseif($this->contains($params[1], '|')){
                            $params[1] = explode('|', $params[1]);
                            if (call_user_func_array(array($this, $params[0]), array($value, $params[1][0], $params[1][1])) == false) {
                                $this->_validations[$name][$params[0]] = false;
                                $this->_errors[$name][$params[0]] = array($value, $params[1][0], $params[1][1]);
                            } else
                                $this->_validations[$name][$params[0]] = true;
                        }else{
                            if (call_user_func_array(array($this, $params[0]), array($value, $params[1])) == false) {
                                $this->_validations[$name][$params[0]] = false;
                                $this->_errors[$name][$params[0]] = array($value, $params[1]);
                            } else
                                $this->_validations[$name][$params[0]] = true;
                        }
                    }else{
                        if (call_user_func(array($this, $this->_requirements[$name]), $value) == false) {
                            $this->_validations[$name][$this->_requirements[$name]] = false;
                            $this->_errors[$name][$this->_requirements[$name]] = array($value);
                        } else
                            $this->_validations[$name][$this->_requirements[$name]] = true;
                    }
                }
            }else{
                $this->_validations[$name] = ['optional' => true];
            }

            if(!$this->required($value) && $this->hasRequirement($name, 'optional')){
                $this->_validations[$name] = ['optional' => true];
            }
        }

        if($this->_errormode == true){
            $msgs = array();
            foreach ($this->_errors as $input => $requirement) {
                foreach ($requirement as $rule => $params) {
                    $msgs[] = $this->getMessage($input, $rule, $params);
                }
            }

            return $msgs;
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
     * @param $input
     * @param $rule
     * @param array $params
     * @return array
     */
    public function getMessage($input, $rule, $params = array())
    {
        $message = $this->_messages[$rule];

        $message = @str_replace(':input', $input, $message);
        $message = @str_replace(':value', $params[0], $message);
        $message = @str_replace(':param1', $params[1], $message);
        $message = @str_replace(':param2', $params[2], $message);
        $message = @str_replace(':prefixes', "'".implode("', '", $this->validUrlPrefixes)."'", $message);

        return $message;
    }

    /**
     * Checks if an input has a requirement
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
        }elseif(empty($value)){
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
            return $this->lengthBetween($value, $min, $max);

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
