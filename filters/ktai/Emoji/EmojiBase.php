<?php

abstract class EmojiBase
{
    protected $carrier = '';
    protected $emoji_map = array();
    protected $emoji_map_reverse = array();

    public function getInternalCode($emoji)
    {
        $code_id = $this->findNumber($emoji);
		$code = '=';
        if ($code_id !== false) {
            $code = $this->carrier . ':' . $code_id;
        }
        return '[' .$code. ']';
    }

   	private function findNumber($emoji)
    {
        return $this->emoji_map_reverse[$emoji];
    }

    public function getWebCode($id, $carrier_prefix = null)
    {
		$code = $this->emoji_map[$id];
		if ($code) {
			return $code;
		} else {
			return '=';
		}
    }
}
