<?php

/*  Dependency :: GD  */

class Image_Edit {

    private $_edit_infos, $_original_info, $_current_info, $_save_flag = true;

    public function  __construct($image_path) {

        $this->_original_info['image_path'] = $image_path;
        $this->_original_info['image_type'] = $this->getImageType(file_get_contents($this->_original_info['image_path']));
        $this->_current_info['image_source'] = $this->getImageSource();
        $this->refresh($image_path);

    }

    public function refresh() {
        
        if(isset($this->_original_info['image_source'])) {
            
            $this->_current_info['image_source'] = $this->_original_info['image_source'];
            
        }
        
        if(isset($this->_original_info['image_sizes'])) {
            
            $this->_current_info['image_size'] = $this->_original_info['image_sizes'];
            
        }
        
        $this->_save_flag = true;
        $this->_edit_infos = array();

    }

    /*  Set  */

    private function setEditInfo($method, $params=array()) {

        $this->_edit_infos[] = array(

            'method' => $method,
            'params' => $params

        );

    }

    public function width($width) {

        $this->setEditInfo('editSize', array('width', $width));

    }

    public function maxWidth($width) {

        $this->setEditInfo('editSize', array('width', $width, 'max'));

    }

    public function minWidth($width) {

        $this->setEditInfo('editSize', array('width', $width, 'min'));

    }

    public function height($height) {

        $this->setEditInfo('editSize', array('height', $height));

    }

    public function maxHeight($height) {

        $this->setEditInfo('editSize', array('height', $height, 'max'));

    }

    public function minHeight($height) {

        $this->setEditInfo('editSize', array('height', $height, 'min'));

    }

    public function size($width, $height) {

        $sizes = array(

            'w' => $width,
            'h' => $height

        );
        $this->setEditInfo('editSize', array('height', $sizes));

    }

    public function rotate($angle) {

        $this->setEditInfo('editRotate', array($angle));

    }

    public function portrait($angle=270) {

        if(!in_array($angle, array(90, 270))) $angle = 270;
        $image_sizes = $this->getSizeInfo();
        $original_width = $image_sizes['w'];
        $original_height = $image_sizes['h'];

        if($original_width > $original_height) {

            $this->rotate($angle);

        }

    }

    public function horizontal($angle=270) {

        $this->rotateMode('horizontal', $angle);

    }

    public function vertical($angle=270) {

        $this->rotateMode('vertical', $angle);

    }

    public function rotateMode($mode, $angle=270) {

        if(!in_array($angle, array(90, 270))) $angle = 270;
        $image_sizes = $this->getSizeInfo();
        $original_width = $image_sizes['w'];
        $original_height = $image_sizes['h'];

        if(($mode == 'horizontal' && $original_width < $original_height)
                || ($mode == 'vertical' && $original_width > $original_height)) {

            $this->setEditInfo('editRotate', array($angle));
        
        }

    }

    /*  Edit  */

    private function editSize($edit_mode, $length, $max_min_mode='') {

        $original_image_sizes = $this->getSizeInfo();
        $original_width = $original_image_sizes['w'];
        $original_height = $original_image_sizes['h'];

        if(is_array($length)) {

            $target_width = $length['w'];
            $target_height = $length['h'];

        } else {

            $target_sizes = $this->getTargetSize($edit_mode, $max_min_mode, $original_image_sizes, $length);
            $target_width = $target_sizes['w'];
            $target_height = $target_sizes['h'];

        }

        $current_image_source = $this->_current_info['image_source'];

        $new_image_source = imagecreatetruecolor($target_width, $target_height);
        $bgcolor = imagecolorallocatealpha($current_image_source, 0, 0, 0, 127);
        imagecolortransparent($new_image_source, $bgcolor);

        imagecopyresampled($new_image_source, $current_image_source, 0, 0, 0, 0, $target_width, $target_height, $original_width, $original_height);
        $this->_current_info['image_source'] = $new_image_source;

    }
    
    private function getTargetSize($edit_mode, $max_min_mode, $original_image_sizes, $length) {

        $returns = array();
        $original_width = $original_image_sizes['w'];
        $original_height = $original_image_sizes['h'];
        
        if($edit_mode == 'width') {
            
            if($max_min_mode == 'max' && $original_width < $length || 
                    $max_min_mode == 'min' && $original_width > $length) {

                $returns = $original_image_sizes;
                $this->_save_flag = false;

            } else {

                $returns = array(
                    
                    'w' => $length, 
                    'h' => $original_height / $original_width * $length
                    
                );

            }
            
        } else if($edit_mode == 'height') {

            if($max_min_mode == 'max' && $original_height < $length || 
                    $max_min_mode == 'min' && $original_height > $length) {

                $returns = $original_image_sizes;
                $this->_save_flag = false;

            } else {

                $returns = array(
                    
                    'w' => $original_height / $original_width * $length, 
                    'h' => $length
                    
                );

            }

        }
        
        return $returns;
        
    }
    
    private function editRotate($angle) {

        $this->_current_info['image_source'] = imagerotate($this->_current_info['image_source'], $angle, 0);

    }

    /*  Image Type  */

    public function imageType() {

        return $this->_original_info['image_type'];

    }

    public function getImageType($image_source) {

        $return = false;

        if(preg_match('/^\x89PNG\x0d\x0a\x1a\x0a/', $image_source)) {

            $return = 'png';

        } else if(preg_match('/^GIF8[79]a/', $image_source)) {

            $return = 'gif';

        } else if(preg_match('/^\xff\xd8/', $image_source)) {

            $return = 'jpg';

        }

        return $return;

    }

    private function checkImageType($expansion) {

        return in_array($expansion, array('png', 'jpg', 'gif'));

    }

    /*  Others  */

    public function save($save_file_path='') {

        if(!$this->checkImageType($this->_original_info['image_type'])) {

            return false;

        }

        if($save_file_path == '') {

            $save_file_path = $this->_original_info['image_path'];  // Overwrite

        }

        $edit_infos = $this->_edit_infos;
        $edit_infos_count = count($edit_infos);

        for($loop = 0; $loop < $edit_infos_count; $loop++) {

            $edit_info = $edit_infos[$loop];
            $method = $edit_info['method'];
            $params = $edit_info['params'];
            call_user_func_array(array($this, $method), $params);

            if($method == 'editRotate') {

                $this->saveImage($save_file_path);
                $this->_current_info['image_size'] = false;
                $this->_original_info['image_path'] = $save_file_path;

            }

        }

        if(!$this->_save_flag) return true;
        $return = $this->saveImage($save_file_path);
        imagedestroy($this->_current_info['image_source']);
        return $return;
        
    }

    private function saveImage($file_path) {

        $return = false;
        $image_source = $this->_current_info['image_source'];
        $save_expansion = substr($file_path, -3);

        if(!$this->checkImageType($save_expansion)) {

            $save_expansion = $this->_original_info['image_type'];

        }

        switch($this->_original_info['image_type']) {

            case 'png':
                $return = imagepng($image_source, $file_path);
                break;
            case 'jpg':
                $return = imagejpeg($image_source, $file_path);
                break;
            case 'gif':
                $return = imagegif($image_source, $file_path);
                break;

        }

        return $return;

    }

    private function getImageSource() {

        $return = '';
        $file_path = $this->_original_info['image_path'];

        switch($this->_original_info['image_type']) {

            case 'png':
                $return = imagecreatefrompng($file_path);
                break;
            case 'jpg':
                $return = imagecreatefromjpeg($file_path);
                break;
            case 'gif':
                $return = imagecreatefromgif($file_path);
                break;

        }

        if(!isset($this->_original_info['image_source'])) {

            $this->_original_info['image_source'] = $return;

        }

        return $return;

    }

    private function getSizeInfo() {

        $returns = array();

        if(!isset($this->_current_info['image_size'])) {

            list($width, $height) = getimagesize($this->_original_info['image_path']);

            $returns = array(

                'w' => $width,
                'h' => $height

            );

            if(!isset($this->_original_info['image_sizes'])) {

                $this->_original_info['image_sizes'] = $returns;

            }

        } else {

            $returns = $this->_current_info['image_size'];

        }

        return $returns;

    }

}

/*** Sample Source

require 'image_edit.php';

$image_path = 'image/base.jpg';

$ie = new Image_Edit($image_path);

// Width

$ie->width(200);
$ie->save('test-1.png');

$ie->refresh();

$ie->maxWidth(500);
$ie->save('test-2.jpg');

$ie->refresh();

$ie->minWidth(500);
$ie->save('test-3.gif');

$ie->refresh();

// Height

$ie->height(200);
$ie->save('test-4.png');

$ie->refresh();

$ie->maxHeight(100);
$ie->save('test-5.jpg');

$ie->refresh();

$ie->minHeight(500);
$ie->save('test-6.gif');

$ie->refresh();

// Width & Height

$width = 100;
$height = 120;
$ie->size($width, $height);
$ie->save('test-7.jpg');

$ie->refresh();

// Rotate

$ie->rotate(270);
$ie->save('test-8.jpg');

$ie->refresh();

$ie->vertical(270);
$ie->save('test-9.jpg');

$ie->refresh();

$ie->horizontal(90);
$ie->save('test-10.jpg');

***/
