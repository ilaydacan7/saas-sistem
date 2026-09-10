<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Veritabanının sıkıştırılmış yedeğini alır.
 *
 * Taşınabilirlik notları:
 *  - Dump komutu yapılandırmadan gelir. Yerelde PostgreSQL konteynerde
 *    çalıştığı için "docker exec ... pg_dump", sunucuda düz "pg_dump" olur.
 *  - Sıkıştırma PHP'nin zlib'i ile yapılır; Windows'ta bulunmayabilecek
 *    gzip programına bağımlı kalınmaz.
 *  - Parola komut satırına yazılmaz, PGPASSWORD ortam değişkeniyle geçer;
 *    böylece süreç listesinde görünmez.
 */
class BackupDatabase extends Command
{
    protected $signature = 'yedek:al
        {--tut= : Kaç günlük yedek saklansın}';

    protected $description = 'Veritabanının yedeğini alır ve eski yedekleri temizler';

    public function handle(): int
    {
        $baglanti = config('database.default');

        if ($baglanti !== 'pgsql') {
            $this->error("Bu komut PostgreSQL içindir. Etkin bağlantı: {$baglanti}");

            return self::FAILURE;
        }

        $ayar = config('database.connections.pgsql');
        $klasor = rtrim((string) config('backup.path'), '/\\');

        File::ensureDirectoryExists($klasor);

        $damga = now()->format('Y-m-d_His');
        $hamDosya = $klasor.DIRECTORY_SEPARATOR.$ayar['database'].'-'.$damga.'.sql';
        $hedef = $hamDosya.'.gz';

        if (! $this->dokum($ayar, $hamDosya)) {
            File::delete($hamDosya);

            return self::FAILURE;
        }

        if (! $this->sikistir($hamDosya, $hedef)) {
            $this->error('Yedek sıkıştırılamadı.');
            File::delete($hamDosya);

            return self::FAILURE;
        }

        File::delete($hamDosya);

        $this->info(sprintf('Yedek alındı: %s (%s)', basename($hedef), $this->boyut(File::size($hedef))));

        $this->eskileriTemizle($klasor, (int) ($this->option('tut') ?? config('backup.keep_days')));

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $ayar
     */
    private function dokum(array $ayar, string $hedef): bool
    {
        $komut = sprintf(
            '%s --host=%s --port=%s --username=%s --dbname=%s --no-owner --no-privileges > %s',
            config('backup.dump_command'),
            escapeshellarg((string) $ayar['host']),
            escapeshellarg((string) $ayar['port']),
            escapeshellarg((string) $ayar['username']),
            escapeshellarg((string) $ayar['database']),
            escapeshellarg($hedef),
        );

        $surec = Process::fromShellCommandline($komut, base_path(), ['PGPASSWORD' => (string) $ayar['password']], null, 600);
        $surec->run();

        if (! $surec->isSuccessful()) {
            $this->error('Yedek alınamadı: '.trim($surec->getErrorOutput() ?: 'dump komutu çalıştırılamadı.'));
            $this->line('Kullanılan komut: '.config('backup.dump_command'));
            $this->line('Yerelde PostgreSQL konteynerde çalışıyorsa .env içine şunu ekleyin:');
            $this->line('  BACKUP_DUMP_COMMAND="docker exec -i -e PGPASSWORD saas_postgres pg_dump"');

            return false;
        }

        if (! File::exists($hedef) || File::size($hedef) === 0) {
            $this->error('Yedek dosyası boş üretildi.');

            return false;
        }

        return true;
    }

    private function sikistir(string $kaynak, string $hedef): bool
    {
        $girdi = fopen($kaynak, 'rb');
        $cikti = gzopen($hedef, 'wb9');

        if ($girdi === false || $cikti === false) {
            return false;
        }

        while (! feof($girdi)) {
            $parca = fread($girdi, 1024 * 512);

            if ($parca === false) {
                break;
            }

            gzwrite($cikti, $parca);
        }

        fclose($girdi);
        gzclose($cikti);

        return File::exists($hedef) && File::size($hedef) > 0;
    }

    private function eskileriTemizle(string $klasor, int $gun): void
    {
        if ($gun < 1) {
            return;
        }

        $sinir = now()->subDays($gun)->timestamp;
        $silinen = 0;

        foreach (File::files($klasor) as $dosya) {
            if (! str_ends_with($dosya->getFilename(), '.sql.gz')) {
                continue;
            }

            if ($dosya->getMTime() < $sinir) {
                File::delete($dosya->getPathname());
                $silinen++;
            }
        }

        if ($silinen > 0) {
            $this->line("{$gun} günden eski {$silinen} yedek silindi.");
        }
    }

    private function boyut(int $bayt): string
    {
        foreach (['B', 'KB', 'MB'] as $birim) {
            if ($bayt < 1024) {
                return round($bayt, 1).' '.$birim;
            }

            $bayt = (int) ($bayt / 1024);
        }

        return $bayt.' GB';
    }
}
