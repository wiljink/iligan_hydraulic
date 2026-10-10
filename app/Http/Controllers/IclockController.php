<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\BiometricDevice;
use App\Services\Payroll\BiometricImporter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * ZKTeco ADMS / "iClock" push protocol.
 *
 * On the device: Menu > Comm. > Cloud Server Setting (ADMS) > server address = this site,
 * port 80/443. The device then:
 *   GET  /iclock/cdata?SN=...&options=all        handshake, we reply with its settings
 *   POST /iclock/cdata?SN=...&table=ATTLOG       new punches, one per line: PIN \t YYYY-MM-DD HH:MM:SS \t ...
 *   GET  /iclock/getrequest?SN=...               polls for commands (we have none)
 *
 * Only devices registered under HR > Attendance (by serial number) are accepted. Anything
 * else gets a 403, so the device keeps the punches and resends them once it's registered.
 */
class IclockController extends Controller
{
    public function handshake(Request $request): Response
    {
        $device = $this->device($request);
        if (! $device) {
            return $this->text('UNKNOWN DEVICE', 403);
        }

        $serial = $device->serial_number;

        return $this->text(implode("\n", [
            "GET OPTION FROM: {$serial}",
            'ATTLOGStamp=None',
            'OPERLOGStamp=9999',
            'ATTPHOTOStamp=None',
            'ErrorDelay=30',
            'Delay=10',
            'TransTimes=00:00;14:05',
            'TransInterval=1',
            'TransFlag=TransData AttLog',
            'TimeZone=8',
            'Realtime=1',
            'Encrypt=None',
        ]));
    }

    public function receive(Request $request, BiometricImporter $importer): Response
    {
        $device = $this->device($request);
        if (! $device) {
            return $this->text('UNKNOWN DEVICE', 403);
        }

        // Only attendance records matter; acknowledge everything else (operation logs, photos, user info).
        if (strtoupper((string) $request->query('table')) !== 'ATTLOG') {
            return $this->text('OK');
        }

        $parsed = $importer->parseText($request->getContent());
        $importer->store($parsed['rows'], $parsed['invalid'], null, AttendanceLog::SOURCE_DEVICE, $device->serial_number);

        return $this->text('OK: '.count($parsed['rows']));
    }

    public function poll(Request $request): Response
    {
        return $this->device($request) ? $this->text('OK') : $this->text('UNKNOWN DEVICE', 403);
    }

    private function device(Request $request): ?BiometricDevice
    {
        $device = BiometricDevice::where('serial_number', (string) $request->query('SN'))->first();
        $device?->forceFill(['last_seen_at' => now()])->save();

        return $device;
    }

    private function text(string $body, int $status = 200): Response
    {
        return response($body, $status)->header('Content-Type', 'text/plain');
    }
}
