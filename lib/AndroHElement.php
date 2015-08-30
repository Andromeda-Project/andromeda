<?php

class AndroHElement
{
    public $style = array();
    public $atts = array();

    public function __construct($type)
    {
        $this->type = $type;
        $this->children = array();
        $this->atts = array();
        $this->innerHTML = '';
    }

    public function appendChild($object)
    {
        $this->children[] = $object;
    }

    public function render($indent = 0)
    {
        $hIndent = str_pad('', $indent * 3);

        $retval = "\n$hIndent<" . $this->type;

        // Do style attributes
        $hstyle = '';
        foreach ($this->style as $stylename => $value) {
            $hstyle.= "$stylename: $value;";
        }
        if ($hstyle <> '') {
            $this->atts['style'] = $hstyle;
        }

        // Now output the attributes
        foreach ($this->atts as $name => $value) {
            $retval.= " $name = \"$value\"";
        }
        $retval.= ">";
        foreach ($this->children as $onechild) {
            $retval.= $onechild->render($indent + 1);
        }
        $retval.= $this->innerHTML;
        $retval.= "\n$hIndent</" . $this->type . ">";
        return $retval;
    }
}
