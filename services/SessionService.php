<?php
/**
 * SessionService - Handle pending expenses untuk konfirmasi sebelum save
 */

require_once __DIR__ . '/../database.php';

class SessionService
{
    private PDO $db;
    private int $expiryMinutes = 5;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->cleanupExpired();
    }

    /**
     * Simpan pending expenses untuk konfirmasi
     * @param string $phone Nomor telepon user
     * @param array $expenses Array of expense data
     * @return bool
     */
    public function savePending(string $phone, array $expenses): bool
    {
        // Hapus pending yang ada untuk phone ini
        $this->clearPending($phone);

        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$this->expiryMinutes} minutes"));
        
        $stmt = $this->db->prepare(
            'INSERT INTO pending_expenses (phone, expenses_data, expires_at) VALUES (?, ?, ?)'
        );
        
        return $stmt->execute([
            $phone,
            json_encode($expenses),
            $expiresAt
        ]);
    }

    /**
     * Ambil pending expenses untuk phone tertentu
     * @param string $phone
     * @return array|null
     */
    public function getPending(string $phone): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM pending_expenses WHERE phone = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$phone]);
        $result = $stmt->fetch();

        if (!$result) {
            return null;
        }

        return [
            'id' => $result['id'],
            'phone' => $result['phone'],
            'expenses' => json_decode($result['expenses_data'], true),
            'created_at' => $result['created_at'],
            'expires_at' => $result['expires_at']
        ];
    }

    /**
     * Cek apakah ada pending untuk phone tertentu
     * @param string $phone
     * @return bool
     */
    public function hasPending(string $phone): bool
    {
        return $this->getPending($phone) !== null;
    }

    /**
     * Hapus pending expenses untuk phone tertentu
     * @param string $phone
     * @return bool
     */
    public function clearPending(string $phone): bool
    {
        $stmt = $this->db->prepare('DELETE FROM pending_expenses WHERE phone = ?');
        return $stmt->execute([$phone]);
    }

    /**
     * Hapus semua pending yang sudah expired
     */
    public function cleanupExpired(): void
    {
        $this->db->exec('DELETE FROM pending_expenses WHERE expires_at <= NOW()');
    }

    /**
     * Cek apakah input adalah konfirmasi (y/n/g)
     * @param string $message
     * @return string|null 'yes', 'no', atau null jika bukan konfirmasi
     */
    public function parseConfirmation(string $message): ?string
    {
        $message = strtolower(trim($message));
        
        // Ya
        if (in_array($message, ['y', 'ya', 'yes', 'ok', 'oke', 'iya', 'yoi', 'yup', 'sip'])) {
            return 'yes';
        }
        
        // Tidak
        if (in_array($message, ['n', 'g', 'no', 'tidak', 'nggak', 'gak', 'cancel', 'batal', 'ga', 'enggak'])) {
            return 'no';
        }
        
        return null;
    }

    /**
     * Format pending expenses untuk pesan konfirmasi
     * @param array $expenses
     * @return string
     */
    public function formatConfirmationMessage(array $expenses): string
    {
        $count = count($expenses);
        
        if ($count === 1) {
            $exp = $expenses[0];
            $desc = $exp['description'] ? " ({$exp['description']})" : '';
            return sprintf(
                "Konfirmasi catat?\n%s Rp%s%s\n\nReply: y=ya, n/g=tidak",
                ucfirst($exp['category']),
                number_format($exp['amount'], 0, ',', '.'),
                $desc
            );
        }
        
        // Multiple expenses
        $total = 0;
        $lines = ["Konfirmasi catat {$count} pengeluaran?"];
        
        foreach ($expenses as $i => $exp) {
            $num = $i + 1;
            $desc = $exp['description'] ? " ({$exp['description']})" : '';
            $lines[] = sprintf(
                "%d. %s Rp%s%s",
                $num,
                ucfirst($exp['category']),
                number_format($exp['amount'], 0, ',', '.'),
                $desc
            );
            $total += $exp['amount'];
        }
        
        $lines[] = sprintf("\nTotal: Rp%s", number_format($total, 0, ',', '.'));
        $lines[] = "\nReply: y=ya, n/g=tidak";
        
        return implode("\n", $lines);
    }
}
