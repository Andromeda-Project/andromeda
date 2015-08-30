<?php
class AndroPlugin
{
    public $type;
    public $reserved;

    public function __construct()
    {
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function parseArgs($args)
    {
        $this->reserved = array(
            'type',
            'reserved'
        );
        if ($this->type == 'UI') {
            $arr1 = split(",", $args);
            foreach ($arr1 as $ar1) {
                $arr2 = split(";", $ar1);
                if (in_array($arr2[0], $this->reserved)) {
                    trigger_error("AndroPlugin: Reserved word \"" . $arr2[0] ."\" cannot be used", E_USER_ERROR);
                } else {
                    $this->{$arr2[0]} = $arr2[1];
                }
            }
        } else {
            trigger_error("AndroPlugin: parseArgs is only valid for UI type plugins", E_USER_ERROR);
        }
    }
}
