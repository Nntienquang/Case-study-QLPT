<?php

class ListingQuality
{
    public static function evaluate(array $motel): array
    {
        $score = 0;
        $missing = [];
        $suggestions = [];

        if (self::filled($motel, 'title')) {
            $score += 12;
        } else {
            $missing[] = 'Tieu de';
            $suggestions[] = 'Them tieu de ro khu vuc, loai phong va diem manh.';
        }

        $description = trim((string)($motel['description'] ?? ''));
        $descriptionLength = function_exists('mb_strlen') ? mb_strlen($description, 'UTF-8') : strlen($description);
        if ($descriptionLength >= 80) {
            $score += 18;
        } elseif ($description !== '') {
            $score += 8;
            $suggestions[] = 'Mo ta nen co it nhat 80 ky tu: noi that, gio giac, tien ich, quy dinh.';
        } else {
            $missing[] = 'Mo ta';
            $suggestions[] = 'Bo sung mo ta chi tiet de user tin tuong hon.';
        }

        if ((int)($motel['price'] ?? 0) > 0) {
            $score += 14;
        } else {
            $missing[] = 'Gia thue';
            $suggestions[] = 'Nhap gia thue theo thang.';
        }

        if (self::filled($motel, 'address')) {
            $score += 12;
        } else {
            $missing[] = 'Dia chi';
            $suggestions[] = 'Nhap dia chi cu the de user uoc tinh vi tri.';
        }

        if ((float)($motel['area'] ?? 0) > 0) {
            $score += 10;
        } else {
            $missing[] = 'Dien tich';
            $suggestions[] = 'Nhap dien tich phong theo m2.';
        }

        if ((int)($motel['category_id'] ?? 0) > 0) {
            $score += 8;
        } else {
            $missing[] = 'Danh muc';
        }

        if ((int)($motel['district_id'] ?? 0) > 0) {
            $score += 8;
        } else {
            $missing[] = 'Quan/huyen';
        }

        if (self::filled($motel, 'utilities')) {
            $score += 8;
        } else {
            $missing[] = 'Tien nghi';
            $suggestions[] = 'Chon tien nghi co san nhu Wi-Fi, may lanh, cho de xe.';
        }

        if (self::filled($motel, 'available_from')) {
            $score += 4;
        } else {
            $suggestions[] = 'Them ngay co the vao o de user quyet dinh nhanh hon.';
        }

        if ((int)($motel['service_fee'] ?? 0) >= 0 && isset($motel['service_fee'])) {
            $score += 3;
        }

        if ((float)($motel['deposit_months'] ?? 0) > 0) {
            $score += 3;
        } else {
            $suggestions[] = 'Nhap so thang coc de tinh chi phi vao o.';
        }

        return [
            'score' => min(100, $score),
            'missing_fields' => implode(', ', array_unique($missing)),
            'suggestions' => implode(' ', array_unique($suggestions)),
        ];
    }

    public static function sync(mysqli $conn, array $motel): array
    {
        $quality = self::evaluate($motel);
        $motelId = (int)($motel['id'] ?? 0);

        if ($motelId > 0 && (int)($motel['health_score'] ?? -1) !== $quality['score']) {
            $stmt = $conn->prepare('UPDATE motels SET health_score = ? WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('ii', $quality['score'], $motelId);
                $stmt->execute();
                $stmt->close();
            }

            $stmt = $conn->prepare('INSERT INTO listing_quality_checks (motel_id, score, missing_fields, suggestions) VALUES (?, ?, ?, ?)');
            if ($stmt) {
                $stmt->bind_param('iiss', $motelId, $quality['score'], $quality['missing_fields'], $quality['suggestions']);
                $stmt->execute();
                $stmt->close();
            }
        }

        return $quality;
    }

    public static function label(int $score): string
    {
        if ($score >= 80) {
            return 'Tot';
        }
        if ($score >= 60) {
            return 'Can bo sung';
        }
        return 'Thieu thong tin';
    }

    public static function badgeClass(int $score): string
    {
        if ($score >= 80) {
            return 'success';
        }
        if ($score >= 60) {
            return 'warning';
        }
        return 'danger';
    }

    private static function filled(array $data, string $key): bool
    {
        return isset($data[$key]) && trim((string)$data[$key]) !== '';
    }
}
