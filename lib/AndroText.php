<?php
// ==============================================================
//
// SECTION: Generate plaintext business reports
//
// ==============================================================
class AndroText
{
    public $pages = array();
    public $topMargin = 6;
    public $leftMargin = 5;
    public $cpi = 10;
    public $cpl = 85;
    public $lpp = 66;

    public function __construct($topMargin = 6, $leftMargin = 5, $cpi = 10)
    {
        $this->topMargin = $topMargin;
        $this->leftMargin = $leftMargin;
        $this->cpi = $cpi;

        $this->cpl = ($this->cpi * 8.5) - $this->leftMargin;
    }

    public function newPage()
    {
        $this->pages[] = array();
    }

    public function box($line, $position, $text, $orientation = 'L')
    {
        // Adjust for margins
        $line+= $this->topMargin;
        if ($orientation == 'R' && $position == 0) {
            $position = $this->cpl;
        } else {
            $position+= $this->leftMargin;
        }
        // Always add a page if there is not one, then fetch
        // the page number
        if (count($this->pages) == 0) {
            $this->newPage();
        }
        $page = count($this->pages) - 1;
        // Create the line if it is not there, retrieve it
        if (!isset($this->pages[$page][$line])) {
            $lineLength = $this->cpl - $this->leftMargin;
            $this->pages[$page][$line] = str_repeat(' ', $lineLength);
        }
        $lineText = $this->pages[$page][$line];
        // If centered, work out the position and then fake
        // it as a left-oriented.
        if ($orientation == 'C') {
            $position = intval(($this->cpl - strlen($text)) / 2);
            $orientation = 'L';
        }
        // The only real switch is on orientation.  Otherwise
        // we are doing straight string substitution
        if ($orientation == 'L') {
            $lineText = substr($lineText, 0, $position - 1) . $text . substr($lineText, ($position + strlen($text)) - 1);
        } else {
            if ($position == 0) {
                $position = $this->cpl;
            }
            $lineText = substr($lineText, 0, $position - strlen($text)) . $text . substr($lineText, $position);
        }
        $this->pages[$page][$line] = $lineText;
    }

    public function renderAsText()
    {
        $text = '';
        foreach ($this->pages as $pagelines) {
            for ($x = 1; $x <= 66; $x++) {
                if (isset($pagelines[$x])) {
                    $text.= $pagelines[$x];
                } else {
                    $text.= str_repeat(' ', $this->cpl);
                }
                $text.= "\n";
            }
        }
        return $text;
    }
}
