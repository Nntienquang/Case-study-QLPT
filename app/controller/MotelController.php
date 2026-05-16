<?php

/**
 * Motel Controller
 */

class MotelController
{
    private $motel;
    private $db;
    private $activityLog;

    public function __construct($db, $activityLog = null)
    {
        $this->motel = new Motel($db);
        $this->db = $db;
        $this->activityLog = $activityLog;
    }

    /**
     * List all motels
     */
    public function listMotels()
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $status = isset($_GET['status']) ? $_GET['status'] : '';

        $motels = $this->motel->getAll($page, ITEMS_PER_PAGE, $status);
        $total = $this->motel->getTotal($status);
        $total_pages = ceil($total / ITEMS_PER_PAGE);

        return [
            'motels' => $motels,
            'total' => $total,
            'page' => $page,
            'total_pages' => $total_pages,
            'status' => $status
        ];
    }

    /**
     * View motel details
     */
    public function viewMotel()
    {
        if (!isset($_GET['id'])) {
            header('Location: ' . ADMIN_URL . 'motels.php');
            exit;
        }

        $id = (int)$_GET['id'];
        $motel = $this->motel->getById($id);

        if (!$motel) {
            header('Location: ' . ADMIN_URL . 'motels.php');
            exit;
        }

        $images = $this->motel->getImages($id);
        $utilities = $this->motel->getUtilities($id);

        return [
            'motel' => $motel,
            'images' => $images,
            'utilities' => $utilities
        ];
    }

    /**
     * Approve motel
     */
    public function approveMotel()
    {
        if (!isset($_POST['id'], $_POST['action'])) {
            header('Location: ' . ADMIN_URL . 'motels.php');
            exit;
        }

        $id = (int)$_POST['id'];
        $motel = $this->motel->getById($id);

        if ($this->motel->approve($id)) {
            if ($this->activityLog && $motel) {
                $this->activityLog->log(
                    $_SESSION['user_id'],
                    'approve_motel',
                    'motel',
                    $id,
                    ['old' => $motel['status'], 'new' => 'approved'],
                    "Duyệt phòng trọ: {$motel['title']}"
                );
            }
            $_SESSION['success'] = 'Duyệt phòng thành công';
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra';
        }

        header('Location: ' . ADMIN_URL . 'motels.php');
        exit;
    }

    /**
     * Hide motel
     */
    public function hideMotel()
    {
        if (!isset($_POST['id'], $_POST['action'])) {
            header('Location: ' . ADMIN_URL . 'motels.php');
            exit;
        }

        $id = (int)$_POST['id'];
        $motel = $this->motel->getById($id);

        if ($this->motel->hide($id)) {
            if ($this->activityLog && $motel) {
                $this->activityLog->log(
                    $_SESSION['user_id'],
                    'hide_motel',
                    'motel',
                    $id,
                    ['old' => $motel['status'], 'new' => 'hidden'],
                    "Ẩn phòng trọ: {$motel['title']}"
                );
            }
            $_SESSION['success'] = 'Ẩn phòng thành công';
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra';
        }

        header('Location: ' . ADMIN_URL . 'motels.php');
        exit;
    }

    /**
     * Delete motel
     */
    public function deleteMotel()
    {
        if (!isset($_POST['id'], $_POST['action'])) {
            header('Location: ' . ADMIN_URL . 'motels.php');
            exit;
        }

        $id = (int)$_POST['id'];
        $motel = $this->motel->getById($id);

        if ($this->motel->delete($id)) {
            if ($this->activityLog && $motel) {
                $this->activityLog->log(
                    $_SESSION['user_id'],
                    'delete_motel',
                    'motel',
                    $id,
                    [],
                    "Xóa phòng trọ: {$motel['title']}"
                );
            }
            $_SESSION['success'] = 'Xóa phòng thành công';
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra';
        }

        header('Location: ' . ADMIN_URL . 'motels.php');
        exit;
    }

    public function rejectMotel()
    {
        if (!isset($_POST['id'], $_POST['action'])) {
            header('Location: ' . ADMIN_URL . 'motels.php');
            exit;
        }

        $id = (int)$_POST['id'];
        $reason = trim((string)($_POST['rejection_reason'] ?? ''));
        $motel = $this->motel->getById($id);

        if (!$motel || $reason === '') {
            $_SESSION['error'] = 'Vui lòng nhập lý do từ chối tin phòng.';
            header('Location: ' . ADMIN_URL . 'motels.php');
            exit;
        }

        if ($this->motel->reject($id, $reason, (int)($_SESSION['user_id'] ?? 0))) {
            if ($this->activityLog) {
                $this->activityLog->log(
                    $_SESSION['user_id'],
                    'reject_motel',
                    'motel',
                    $id,
                    ['old' => $motel['status'], 'new' => 'rejected'],
                    "Từ chối phòng trọ: {$motel['title']}. Lý do: {$reason}"
                );
            }
            $_SESSION['success'] = 'Đã từ chối tin phòng.';
        } else {
            $_SESSION['error'] = 'Không thể từ chối tin phòng.';
        }

        header('Location: ' . ADMIN_URL . 'motels.php');
        exit;
    }
}
