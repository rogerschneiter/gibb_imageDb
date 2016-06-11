<?php

require_once "/model/TagModel.php";
require_once "/model/entities/Image.php";
require_once "/model/Model.php";
require_once "DatabaseInterface.php";


/**
 * Created by PhpStorm.
 * User: bbuerf
 * Date: 26.05.2016
 * Time: 13:29
 */
class ImageModel extends Model implements DatabaseInterface
{
    /**
     * @var TagModel
     */
    private $tagModel;

    /**
     * ImageModel constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->tagModel = new TagModel();
    }


    /**
     * @param String $file
     * @param String $thumbnail
     * @param String $name
     * @param int $galleryId
     * @param array $tags Tag
     * @return Image
     */
    public function create($file, $thumbnail, $name, $galleryId, $tags = array()){
        $stmt =$this->db->prepare('Insert into image (file, thumbnail, name, gallery_galleryId) VALUES (?,?,?,?)');
        $stmt->bind_param('sssi',$file,$thumbnail,$name,$galleryId);
        
        foreach($tags as $tag){
            $this->tagModel->save($tag);
        }
        if($stmt->execute()){
            return new Image($this->db->insert_id,$file,$thumbnail,$name,$tags);
        } else echo $stmt->error;
    }

    /**
     * @param $object Image
     * @return Image
     */
    public function save($object)
    {
        return $this->create($object->getFile(),$object->getThumbnail(),$object->getName(),$object->getTags());
    }

    public function readById($id)
    {
        $stmt = $this->db->prepare('SELECT * FROM image where imageId = ?');
        $stmt->bind_param("i",$id);
        if ($stmt->execute()) {
            $result = $stmt->get_result()->fetch_assoc();
            $tags = $this->tagModel->readByImage($id);
            return new Image($result["imageId"],$result["file"],$result["thumbnail"],$result["name"],$tags);
        }

        return null;
    }

    public function readAll()
    {
        $stmt = $this->db->prepare("SELECT * FROM imagedb.image");
        $result = array();
        if ($stmt->execute()) {
            while($row = $stmt->get_result()->fetch_assoc()){
                $tags = array();
                $tags = $this->tagModel->readByImage($row["imageId"]);
                $result[] = new Image($row["imageId"],$row["file"],$row["thumbnail"],$row['name'],$tags);
            }
            return $result;
        }
        return null;
    }
    
    public function readByGallery($galleryId){
        $stmt = $this->db->prepare('Select * From imagedb.image where gallery_galleryId = ?');
        $stmt->bind_param('i',$galleryId);
        $result = array();
        if ($stmt->execute()) {
            $stmtResult = $stmt->get_result();
            while($row = $stmtResult->fetch_assoc()){
                $tags = array();
                $tags = $this->tagModel->readByImage($row["imageId"]);
                $result[] = new Image($row["imageId"],$row["file"],$row["thumbnail"],$row['name'],$tags);
            }
            return $result;
        }
        return null;
    
    }

    public function update($object)
    {
        // TODO: Implement update() method.
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("delete from image WHERE imageId = ?");
        $stmt->bind_param("i",$id);
        return $stmt->execute();
    }
}