<?php
require_once "entities/User.php";
require_once "Model.php";
require_once "GalleryModel.php";
require_once "UserModel.php";
require_once "entities/Gallery.php";
require_once "DatabaseInterface.php";

/**
 * Created by PhpStorm.
 * User: bbuerf
 * Date: 05.06.2016
 * Time: 14:18
 */
class AccessModel extends Model
{
    private $userModel;
    private $imageModel;

    /**
     * AccessModel constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->imageModel = new ImageModel();
    }


    public function getReadUsers($galleryId)
    {
        $stmt = $this->db->prepare("SELECT user_userId FROM imagedb.gallery_user_rolle WHERE gallery_galleryId = ? AND isOwner = FALSE ");
        $stmt->bind_param('i', $galleryId);
        $result = array();
        if ($stmt->execute()) {
            while ($row = $stmt->get_result()->fetch_assoc()) {
                $result[] = $this->userModel->readById($row["user_userId"]);
            }
            return $result;
        }
        return null;
    }


    public function getRelatedGalleries($userId, $isOwnerRelationship)
    {
        $stmt = $this->db->prepare("SELECT gallery_galleryId ,name FROM imagedb.gallery_user_rolle JOIN imagedb.gallery ON gallery_user_rolle.gallery_galleryId = gallery.galleryId WHERE user_userId  = ? AND isOwner = ?");
        $stmt->bind_param('ii', $userId, $isOwnerRelationship);
        $result = array();
        if ($stmt->execute()) {
            while ($row = $stmt->get_result()->fetch_assoc()) {
                $gId = $row["gallery_galleryId"];
                $gName = $row["name"];
                $images = $this->imageModel->readByGallery($gId);
                $result[] = new Gallery($gId,$gName,$images,$this->getOwner($gId),$this->getReadUsers($gId));
            }
            return $result;
        }
        return null;
    }

    public function getOwnGalleries($userId)
    {
        return $this->getRelatedGalleries($userId, true);
    }

    public function getReadGalleries($userId)
    {
        return $this->getRelatedGalleries($userId, false);
    }

    private function grantAccess($userId, $galleryId, $isOwner)
    {
        $stmt = $this->db->prepare("INSERT INTO imagedb.gallery_user_rolle (isOwner, user_userId, gallery_galleryId) VALUES (?, ?,?)");
        $stmt->bind_param('bii', $isOwner, $userId, $galleryId);
        return $stmt->execute();
    }

    public function grantReadAccess($userId, $galleryId)
    {
        return $this->grantAccess($userId, $galleryId, false);
    }

    public function setOwner($userId, $galleryId)
    {
        return $this->grantAccess($userId, $galleryId, true);

    }

    public function getOwner($galleryId)
    {
        $stmt = $this->db->prepare("SELECT user_userId FROM imagedb.gallery_user_rolle WHERE gallery_galleryId = ? AND isOwner = TRUE");
        $stmt->bind_param('i', $galleryId);
        if ($stmt->execute()) {
            $result = $stmt->get_result()->fetch_assoc();
            $userId = $result["user_userId"];
            return $this->userModel->readById($userId);
        }
        return null;

    }
}