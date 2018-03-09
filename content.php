<?php
/**
 * Created by PhpStorm.
 * User: Youi
 * Date: 2015-12-08
 * Time: 20:57
 */

class Content {

    private static function decodeHtmlChars($str) {
        $convertMap = [0x0, 0x2FFFF, 0, 0xFFFF];

        return mb_decode_numericentity($str, $convertMap, 'UTF-8');
    }

    public static function parsePostType($postInfo) {
        if ($postInfo['type']) {
            return strtolower($postInfo['type']);
        } else {
            return false;
        }
    }

    public static function parseAnswer($postInfo) {
        $question = static::decodeHtmlChars($postInfo['question']);
        $answer   = static::decodeHtmlChars($postInfo['answer']);
        $tags     = implode(', ', isset($postInfo['tags']) ? $postInfo['tags'] : []);
        $output   = "[Q&A]\r\n\r\n$question\r\n\r\n$answer\r\n\r\nTags: $tags\r\n";

        return htmlspecialchars($output);
    }

    public static function parseConversation($postInfo) {
        $conversation_text = '';
        foreach ($postInfo['conversation'] as $item) {
            $conversation_text .= "{$item['label']} {$item['phrase']}\r\n";
        }
        $date = "date: {$postInfo['date']}";
        $url  = "url: {$postInfo['url']}";
        $tags = 'tags: ' . implode(', ', isset($postInfo['tags']) ? $postInfo['tags'] : []);

        $output = "$conversation_text\r\n$date\r\n$tags\r\n$url";

        return nl2br($output);
    }

    public static function parseLink($postInfo) {
        $output = <<< EOD
                        <p>Title: <h3>{$postInfo['link-text']}</h3></p>
                        <p>link: <a href="{$postInfo['link-url']}">{$postInfo['link-url']}</a></p>
                        <p>Description:</p>
                        <p>{$postInfo['link-description']}</p>
EOD;

        return $output;
    }

    public static function parseRegular($postInfo) {
        $output = "<h3>{$postInfo['regular-title']}</h3>\n{$postInfo['regular-body']}";

        return $output;
    }

    public static function parseQuote($postInfo) {
        $output = "Text: {$postInfo['quote-text']}<br>\nSource: {$postInfo['quote-source']}";

        return $output;
    }

    public static function parseAudio($post_info) {
        $html = '';

        if (isset($post_info['audio-caption']))
            $html .= $post_info['audio-caption'];

        if (isset($post_info['audio-embed']))
            $html .= $post_info['audio-embed'];

        return $html;
    }

    public static function parseVideo($post_info) {
        $video_source = $post_info['video-source'];
        if ($video_info = unserialize($video_source)) {
            $video_info = $video_info['o1'];
            if (isset($video_info['video_preview_filename_prefix'])) {
                $video_id   = substr($video_info['video_preview_filename_prefix'], 0, -1);
                return "http://vt.tumblr.com/$video_id.mp4";
            }
        }

        if (preg_match('<src="(.+?)">', $video_source, $match)) {
            return $match[1];
        }

        if (isset($post_info['video-player']) && preg_match('<src="(.+?)">', $post_info['video-player'], $match)) {
            return $match[1];
        }

        return false;
    }

    public static function parsePhoto($post_info) {
        $urls = [];

        if ($post_info['photos']) {
            foreach ($post_info['photos'] as $item) {
                $urls[] = $item['photo-url-1280'];
            }
        }

        else {
            $urls[] = $post_info['photo-url-1280'];
        }

        return $urls;
    }

    public static function getErrorText($msg) {
        $errText = "Error Happened.\r\n";
        $errText .= "URL: {$_GET['url']}\r\n";
        $errText .= "Message: $msg";
        return $errText;
    }

    public static function getImagesDownPage($imageUrls){
        ob_start();
        include_once('images-download-tpl.php');
        return ob_get_clean();
    }

    public static function getHtmlZipPack($htmlStr, $fileName = null, $readmeText = null) {
        require_once('zip.lib.php');
        $zip = new ZipFile();

        $zip->addFile($htmlStr, $fileName ?: date('Y-m-d-H-i-s') . '.htm');

        if ($readmeText)
            $zip->addFile($readmeText, 'readme.txt');

        return $zip->file();
    }

    public static function getImagesZipPack(&$images) {
        require_once('zip.lib.php');
        $zip = new ZipFile(true);

        foreach ($images as $url => &$image) {
            if ($image) {
                $fileName = basename($url);
                $zip->addFile($image, $fileName);
            }
        }

        return $zip->file();
    }

}
