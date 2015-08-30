<?php
/**
 * @deprecated
 */
class XmlTree
{
    public function __construct()
    {
        $this->stack = array(0);
        $this->nodes = array();
    }

    public function openChild($node)
    {

        // Add the node to the flat list, then get reference to it
        $this->nodes[] = & $node;
        $newidx = count($this->nodes) - 1;

        // Add the reference to the kids of current
        $curidx = $this->stack[count($this->stack) - 1];
        $this->nodes[$curidx]['kids'][] = $newidx;

        // Add the reference to the stack, so it is the new current
        $this->stack[] = $newidx;
    }

    public function addData($data)
    {
        $curidx = $this->stack[count($this->stack) - 1];

        // Absolutely do not know why these are here, they are being
        // put in by OO.org's output.
        $data = str_replace(chr(160), '', $data);
        $data = str_replace(chr(194), '', $data);
        $this->nodes[$curidx]['value'].= $data;
    }

    public function closeChild()
    {
        array_pop($this->stack);
    }

    public function nodeCDATA($idx)
    {
        $node = $this->nodes[$idx];
        $retval = '';
        for ($x = 0; $x < count($node['kids']); $x++) {
            $gkid = $this->nodes[$node['kids'][$x]];
            if ($gkid['name'] == 'CDATA') {
                $retval.= $gkid['value'];
                break;
            }
        }
        return $retval;
    }

    public function nodeHTML($idx)
    {
        $retval = '';

        $node = $this->nodes[$idx];
        if ($node['name'] == 'CDATA') {
            // the cdata elements just get added to the output
            $open = $node['value'];
            $close = '';
        } else {
            // but non-cdata elements get new tags and get recursed
            $attsx = array();
            $tag = $node['name'];
            foreach ($node['atts'] as $attname => $attvalue) {
                if ($attname == 'STYLE') {
                    continue;
                }
                $attsx[] = $attname . '="' . $attvalue . '"';
            }
            $hatts = implode(' ', $attsx);

            $open = "<$tag $hatts>";
            $close = "</$tag>";
        }

        $inner = '';
        foreach ($this->nodes[$idx]['kids'] as $kididx) {
            $inner.= $this->nodeHTML($kididx);
        }

        return $open . $inner . $close;
    }
}
