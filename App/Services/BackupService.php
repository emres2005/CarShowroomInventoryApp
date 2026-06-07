<?php
namespace App\Services;

use App\Repositories\BackupRepository;

class BackupService {
    private BackupRepository $repo;
    private string $backupDir;

    public function __construct() {
        $this->repo = new BackupRepository();
        $this->backupDir = defined('BACKUP_DIR') ? BACKUP_DIR : __DIR__ . '/../../backups/';
        
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0750, true);
            file_put_contents($this->backupDir . '.htaccess', "Deny from all\n");
        }
    }

    public function createBackup(string $username): array {
        $ts       = date('Y-m-d_H-i-s');
        $filename = "backup_{$ts}_{$username}.sql";
        $filepath = $this->backupDir . $filename;

        // Note: For real deployments, env credentials should be fetched from config securely.
        $cmd = sprintf(
            'MYSQL_PWD=%s mysqldump --host=%s --user=%s --single-transaction --quick --routines --triggers %s > %s 2>&1',
            escapeshellarg(DB_PASS),
            escapeshellarg(DB_HOST),
            escapeshellarg(DB_USER),
            escapeshellarg(DB_NAME),
            escapeshellarg($filepath)
        );
        exec($cmd, $output, $exitCode);

        if ($exitCode === 0 && file_exists($filepath) && filesize($filepath) > 0) {
            $this->repo->logBackup($filename, $username);
            return ['success' => true, 'filename' => $filename, 'errors' => []];
        } else {
            @unlink($filepath);
            return ['success' => false, 'errors' => ['Backup failed. Exit code: ' . $exitCode]];
        }
    }

    public function restoreBackup(string $filename): array {
        $filepath = $this->backupDir . $filename;

        if (!preg_match('/^backup_[\w\-]+\.sql$/', $filename) || !file_exists($filepath)) {
            return ['success' => false, 'errors' => ['Invalid or missing backup file.']];
        }

        $cmd = sprintf(
            'MYSQL_PWD=%s mysql --host=%s --user=%s %s < %s 2>&1',
            escapeshellarg(DB_PASS),
            escapeshellarg(DB_HOST),
            escapeshellarg(DB_USER),
            escapeshellarg(DB_NAME),
            escapeshellarg($filepath)
        );
        exec($cmd, $output, $exitCode);

        if ($exitCode === 0) {
            return ['success' => true, 'errors' => []];
        } else {
            return ['success' => false, 'errors' => ['Restore failed. Exit code: ' . $exitCode . '. Output: ' . implode(' ', $output)]];
        }
    }

    public function deleteBackup(string $filename): array {
        $filepath = $this->backupDir . $filename;

        if (!preg_match('/^backup_[\w\-]+\.sql$/', $filename) || !file_exists($filepath)) {
            return ['success' => false, 'errors' => ['Invalid or missing backup file.']];
        }

        unlink($filepath);
        $this->repo->deleteLog($filename);
        return ['success' => true, 'errors' => []];
    }

    public function listBackups(): array {
        $files = glob($this->backupDir . '*.sql');
        if (!$files) return [];
        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
        
        $result = [];
        foreach ($files as $fpath) {
            $result[] = [
                'filename' => basename($fpath),
                'size_kb'  => round(filesize($fpath) / 1024, 1),
                'mtime'    => date('Y-m-d H:i:s', filemtime($fpath))
            ];
        }
        return $result;
    }

    public function getLog(int $limit = 30): array {
        return $this->repo->getLog($limit);
    }
}
