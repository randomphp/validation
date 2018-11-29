## Validation

Validate a `$_POST` array:  
Keys without any requirements return true.  
Using `$password` gets the value of another key  
```
$validation = new Validation($_POST);
$validation->requirements([
  'username'        => 'required',
  'password'        => ['required', 'min:8'],
  'confirmPassword  => 'equals:$password',
  'name'            => ['optional', 'min:3']
]);
$validation->validate();
```
If the `optional` requirement is on an input it will return true if empty, when if there is more requirements attached the that input. However, if there is anything in the input, the other requirements will validate the value.  
  
    
You can call them individually as well:
```
$validation = new Validation();
$validation->isArray($array);
$validation->min($string, 12);
```

### Built-in requirements/rules

`hasRequirement`  
`optional`  
`isArray`  
`isInteger`  
`isNumeric`  
`required`  
`equals`  
`different`    
`isString`  
`length`  
`min`  
`max`  
`between`  
`in`  
`ip`  
`ipv4`  
`ipv6`  
`email`  
`emailDNS`  
`url`  
`urlActive`  
`regex`  
`date`  
`dateFromat`  
`dateBefore`  
`dateAfter`  
`isBoolean`  
`creditcard`  
`contains`  
`accepted`  
`slug`  
`alpha`  
`alphaNum`
