<?php 

class JSMin
{
    protected $a = "";
    protected $b = "";
    protected $input = "";
    protected $inputIndex = 0;
    protected $inputLength = 0;
    protected $lookAhead = NULL;
    protected $output = "";

    const ORD_LF = 10;
    const ORD_SPACE = 32;
    const ACTION_KEEP_A = 1;
    const ACTION_DELETE_A = 2;
    const ACTION_DELETE_A_B = 3;

    public static function minify($js)
    {
        $jsmin = new JSMin($js);
        return $jsmin->min();
    }

    public function __construct($input)
    {
        $this->input = str_replace("\r\n", "\n", $input);
        $this->inputLength = strlen($this->input);
    }

    protected function action($command)
    {
        switch( $command ) 
        {
            case self::ACTION_KEEP_A:
                $this->output .= $this->a;
            case self::ACTION_DELETE_A:
                $this->a = $this->b;
                if( $this->a === "'" || $this->a === "\"" ) 
                {
                    while( true ) 
                    {
                        $this->output .= $this->a;
                        $this->a = $this->get();
                        if( $this->a === $this->b ) 
                        {
                            break;
                        }

                        if( ord($this->a) <= self::ORD_LF ) 
                        {
                            throw new JSMinException("Unterminated string literal.");
                        }

                        if( $this->a === "\\" ) 
                        {
                            $this->output .= $this->a;
                            $this->a = $this->get();
                        }

                    }
                }

            case self::ACTION_DELETE_A_B:
                $this->b = $this->next();
                if( $this->b === "/" && ($this->a === "(" || $this->a === "," || $this->a === "=" || $this->a === ":" || $this->a === "[" || $this->a === "!" || $this->a === "&" || $this->a === "|" || $this->a === "?" || $this->a === "{" || $this->a === "}" || $this->a === ";" || $this->a === "\n") ) 
                {
                    $this->output .= $this->a . $this->b;
                    while( true ) 
                    {
                        $this->a = $this->get();
                        if( $this->a === "[" ) 
                        {
                            while( true ) 
                            {
                                $this->output .= $this->a;
                                $this->a = $this->get();
                                if( $this->a === "]" ) 
                                {
                                    break;
                                }

                                if( $this->a === "\\" ) 
                                {
                                    $this->output .= $this->a;
                                    $this->a = $this->get();
                                }
                                else
                                {
                                    if( ord($this->a) <= self::ORD_LF ) 
                                    {
                                        throw new JSMinException("Unterminated regular expression set in regex literal.");
                                    }

                                }

                            }
                        }
                        else
                        {
                            if( $this->a === "/" ) 
                            {
                                break;
                            }

                            if( $this->a === "\\" ) 
                            {
                                $this->output .= $this->a;
                                $this->a = $this->get();
                            }
                            else
                            {
                                if( ord($this->a) <= self::ORD_LF ) 
                                {
                                    throw new JSMinException("Unterminated regular expression literal.");
                                }

                            }

                        }

                        $this->output .= $this->a;
                    }
                    $this->b = $this->next();
                }

        }
    }

    protected function get()
    {
        $c = $this->lookAhead;
        $this->lookAhead = NULL;
        if( $c === NULL ) 
        {
            if( $this->inputIndex < $this->inputLength ) 
            {
                $c = substr($this->input, $this->inputIndex, 1);
                $this->inputIndex += 1;
            }
            else
            {
                $c = NULL;
            }

        }

        if( $c === "\r" ) 
        {
            return "\n";
        }

        if( $c === NULL || $c === "\n" || self::ORD_SPACE <= ord($c) ) 
        {
            return $c;
        }

        return " ";
    }

    protected function isAlphaNum($c)
    {
        return 126 < ord($c) || $c === "\\" || preg_match("/^[\\w\\\$]\$/", $c) === 1;
    }

    protected function min()
    {
        if( 0 == strncmp($this->peek(), "ï", 1) ) 
        {
            $this->get();
            $this->get();
            $this->get();
        }

        $this->a = "\n";
        $this->action(self::ACTION_DELETE_A_B);
        while( $this->a !== NULL ) 
        {
            switch( $this->a ) 
            {
                case " ":
                    if( $this->isAlphaNum($this->b) ) 
                    {
                        $this->action(self::ACTION_KEEP_A);
                    }
                    else
                    {
                        $this->action(self::ACTION_DELETE_A);
                    }

                    break;
                case "\n":
                    switch( $this->b ) 
                    {
                        case "{":
                        case "[":
                        case "(":
                        case "+":
                        case "-":
                        case "!":
                        case "~":
                            $this->action(self::ACTION_KEEP_A);
                            break;
                        case " ":
                            $this->action(self::ACTION_DELETE_A_B);
                            break;
                        default:
                            if( $this->isAlphaNum($this->b) ) 
                            {
                                $this->action(self::ACTION_KEEP_A);
                            }
                            else
                            {
                                $this->action(self::ACTION_DELETE_A);
                            }

                    }
                    break;
                default:
                    switch( $this->b ) 
                    {
                        case " ":
                            if( $this->isAlphaNum($this->a) ) 
                            {
                                $this->action(self::ACTION_KEEP_A);
                                break;
                            }

                            $this->action(self::ACTION_DELETE_A_B);
                            break;
                        case "\n":
                            switch( $this->a ) 
                            {
                                case "}":
                                case "]":
                                case ")":
                                case "+":
                                case "-":
                                case "\"":
                                case "'":
                                    $this->action(self::ACTION_KEEP_A);
                                    break;
                                default:
                                    if( $this->isAlphaNum($this->a) ) 
                                    {
                                        $this->action(self::ACTION_KEEP_A);
                                    }
                                    else
                                    {
                                        $this->action(self::ACTION_DELETE_A_B);
                                    }

                            }
                            break;
                        default:
                            $this->action(self::ACTION_KEEP_A);
                            break;
                    }
            }
        }
        return $this->output;
    }

    protected function next()
    {
        $c = $this->get();
        if( $c === "/" ) 
        {
            switch( $this->peek() ) 
            {
                case "/":
                    while( true ) 
                    {
                        $c = $this->get();
                        if( ord($c) <= self::ORD_LF ) 
                        {
                            return $c;
                        }

                    }
                case "*":
                    $this->get();
                    while( true ) 
                    {
                        switch( $this->get() ) 
                        {
                            case "*":
                                if( $this->peek() === "/" ) 
                                {
                                    $this->get();
                                    return " ";
                                }

                                break;
                            case NULL:
                                throw new JSMinException("Unterminated comment.");
                        }
                    }
                default:
                    return $c;
            }
        }

        return $c;
    }

    protected function peek()
    {
        $this->lookAhead = $this->get();
        return $this->lookAhead;
    }

}


class JSMinException extends Exception
{
}


