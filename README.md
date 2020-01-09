## Validation

### Installation
`composer require randomphp/validation`

### How to
Validate a `$_POST|$array` array:  
Keys without any requirements return true.  
Using `$password` gets the value of another key  

The validation wil normally run of the requirements on each input before returning true or error messages.  
However, if you call the `bail()` before you validate, it will stop after the first error

```
// Without bail() active
$validation = new Validation($_POST|$array);
$validation->requirements([
  'username'        => 'required',
  'password'        => ['required', 'min:8'],
  'confirmPassword  => 'equals:$password',
  'name'            => ['optional', 'min:3']
]);
$passed = $validation->validate();
```

```
// With bail() active
$validation = new Validation($_POST|$array);
$validation->requirements([
  'username'        => 'required',
  'password'        => ['required', 'min:8'],
  'confirmPassword  => 'equals:$password',
  'name'            => ['optional', 'min:3']
]);
$validation->bail();
$passed = $validation->validate();
```

You can use the `__construct()` to do some things or everything.    
Default: `__construct($inputs = null, $requirements = null, $validate = false, $bail = false)` 
```
$validation = new Validation($_POST|$array, $rules|[
  'username'        => 'required',
  'password'        => ['required', 'min:8'],
  'confirmPassword  => 'equals:$password',
  'name'            => ['optional', 'min:3']
]);
$passed = $validation->validate();
```

```
// Here we are telling the __construct() to validate the input by adding the true after $rules
$passed = new Validation($_POST|$array, $rules|[
  'username'        => 'required',
  'password'        => ['required', 'min:8'],
  'confirmPassword  => 'equals:$password',
  'name'            => ['optional', 'min:3']
], $validate = true);
```

```
// You can also set the __construct() to bail after first error
$passed = new Validation($_POST|$array, $rules|[
  'username'        => 'required',
  'password'        => ['required', 'min:8'],
  'confirmPassword  => 'equals:$password',
  'name'            => ['optional', 'min:3']
], $validate = true, $bail = true);
```

If the `optional` requirement is on an input it will return true if empty, when if there is more requirements attached the that input. However, if there is anything in the input, the other requirements will validate the value.  
   
You can call them individually as well:
```
$validation = new Validation();
$validation->isArray($array);
$validation->min($string, 12);
```

### Errors
- To change the `error mode` use `setErrormode($boolean)`. (Default: `TRUE`).
- Error messages only shows if you want to validate an array and not using the validation functions separately.

#### Error messages
To customize your messages, you can call either of these functions:
- `setMessage($rule, $message)` - This will only change one message.
- `setMessages($array)` - This will change multiple messages.

There is some placeholders that can be used when customizing messages:
- `:input` - will show the name-attribute of the input
- `:value` - will show the value of the input
- `:param1` - will show the first parameter of the rule eg. `between:8|10` will show 8
- `:param2` - will show the second parameter of the rule eg. `between:8|10` will show 10
- `:prefixes` - will show `'http://', 'http://', 'ftp://'`

##### Examples on changing messages
```
$validation = new Validation($_POST);

// Change the error message for the rule 'isString'
$validation->setMessage('isString', "':input' must be a string."); // will show "'name' must be a string"

// Change the error message on between, alpha and date
$validation->setMessages([
  'between' => "':input' must be between :param1 and :param2", // will show "'age' must be between 13 and 25"
  'alpha' => "':value' must only alphabetic characters", // will show "'hello123' must only show alphabetic characters"
  'date' => "':value' is not a date" // will show "'hello123' is not a date"
]);
```

### Built-in requirements/rules

- `optional`  
- `isArray`  
- `isInteger`  
- `isNumeric`  
- `required`  
- `equals`  
- `different`    
- `isString`  
- `length`  
- `min`  
- `max`  
- `between`  
- `in`  
- `ip`  
- `ipv4`  
- `ipv6`  
- `email`  
- `emailDNS`  
- `url`  
- `urlActive`  
- `regex`  
- `date`  
- `dateFormat`  
- `dateBefore`  
- `dateAfter`  
- `isBoolean`   
- `contains`  
- `accepted`  
- `slug`  
- `alpha`  
- `alphaNum`
