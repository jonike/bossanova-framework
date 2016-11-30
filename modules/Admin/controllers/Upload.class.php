<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Upload Image Handler
 */
namespace modules\Admin\controllers;

use modules\Admin\Admin;

class Upload extends Admin
{
    /**
     * Manage images upload as nodes in the tree
     */
    public function __default()
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            if (isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name']) {
                // Images extensions
                $images = array('image/png', 'image/gif', 'image/jpg');

                // Extension
                $content = base64_encode(file_get_contents($_FILES['file']['tmp_name']));
                $extension = $this->mime_content_type($_FILES['file']['name'], $_FILES['file']['tmp_name']);

                // File
                $column['attach_mime'] = "'{$extension}'";
                $column['attach'] = "'{$content}'";

                // Save file
                if (isset($_POST['node_id']) && $_POST['node_id']) {
                    $this->query->table("nodes")
                        ->column($column)
                        ->argument(1, "node_id", $_POST['node_id'])
                        ->update()
                        ->execute();

                    echo "<script>parent.nodes_refresh_image();</script>";
                } else {
                    $column['title'] = "'" . $_FILES['file']['name'] . "'";
                    $column['parent_id'] = (int) $this->getParam(2);
                    $column['posted'] = "NOW()";
                    $column['updated'] = "NOW()";
                    $column['module_name'] = "'nodes'";
                    $column['option_name'] = in_array($extension, $images) ? "'images'" : "'attach'";
                    $column['status'] = 1;

                    $this->query->table("nodes")
                        ->column($column)
                        ->Insert()
                        ->execute();
                }
            }

            $this->setLayout(0);
            $this->setView(0);
        }
    }

	/**
	 * Mime type based on a filename
	 */
	public function mime_content_type ($filename, $filesource)
	{
		$mime_types = array(

			'txt' => 'text/plain',
			'htm' => 'text/html',
			'html' => 'text/html',
			'php' => 'text/html',
			'css' => 'text/css',
			'js' => 'application/javascript',
			'json' => 'application/json',
			'xml' => 'application/xml',
			'swf' => 'application/x-shockwave-flash',
			'flv' => 'video/x-flv',

			// images
			'png' => 'image/png',
			'jpe' => 'image/jpg',
			'jpeg' => 'image/jpg',
			'jpg' => 'image/jpg',
			'gif' => 'image/gif',
			'bmp' => 'image/bmp',
			'ico' => 'image/vnd.microsoft.icon',
			'tiff' => 'image/tiff',
			'tif' => 'image/tiff',
			'svg' => 'image/svg+xml',
			'svgz' => 'image/svg+xml',

			// archives
			'zip' => 'application/zip',
			'rar' => 'application/x-rar-compressed',
			'exe' => 'application/x-msdownload',
			'msi' => 'application/x-msdownload',
			'cab' => 'application/vnd.ms-cab-compressed',

			// audio/video
			'mp3' => 'audio/mpeg',
			'qt' => 'video/quicktime',
			'mov' => 'video/quicktime',

			// adobe
			'pdf' => 'application/pdf',
			'psd' => 'image/vnd.adobe.photoshop',
			'ai' => 'application/postscript',
			'eps' => 'application/postscript',
			'ps' => 'application/postscript',

			// ms office
			'doc' => 'application/msword',
			'rtf' => 'application/rtf',
			'xls' => 'application/vnd.ms-excel',
			'ppt' => 'application/vnd.ms-powerpoint',

			// open office
			'odt' => 'application/vnd.oasis.opendocument.text',
			'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
		);

		$ext = explode('.', $filename);
		$ext = $ext[count($ext)-1];
		$ext = strtolower($ext);

		if (array_key_exists($ext, $mime_types)) {
			return $mime_types[$ext];
		} elseif (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME);
			$mimetype = finfo_file($finfo, $filesource);
			finfo_close($finfo);
			return $mimetype;
		} else {
			return 'application/octet-stream';
		}
	}
}
