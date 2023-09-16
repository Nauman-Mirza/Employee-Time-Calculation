<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Models\EmployeeTime;

class userController extends Controller
{
    public function go()
    {
        $response = Http::get('https://backend.grabdata.org/api/pf');
        $data= $response->json();
        
        if (isset($data['data'])) 
        {
            $ipAddresses = [];
            $check_in=[];
            $check_out=[];
            $userData = [];
            $date=[];

            foreach ($data['data'] as $user) 
            {
                $userData[] = [
                    'user_id'=> $user['user_id'],
                    'ipAddress' => $user['ip_address'],
                    'check_in' => $user['checked_in_at'],
                    'check_out' => $user['checked_out_at']
                ];
            }

            return $this->calculate($userData);
        }
    }

    public function calculate($userData)
    {
        foreach ($userData as &$user) {
            $checkInTime = Carbon::parse($user['check_in']);
            $checkOutTime = Carbon::parse($user['check_out']);
            // $timeDifference = $checkInTime->diffInHours($checkOutTime);
            $timeDifference = $checkInTime->diffInMinutes($checkOutTime);
    
            $resultData[] = [
                'user_id'=> $user['user_id'],
                'ipAddress' => $user['ipAddress'],
                'totalTime' => $timeDifference
            ];
        }

        return $this->checkingOfficeIP($resultData);
    }

    public function checkingOfficeIP($resultData)
    {
        $updatedData=[];

        foreach ($resultData as &$user) {    
            if(($user['ipAddress'] == '192.168.1.100') || ($user['ipAddress'] == '10.0.0.1') ||($user['ipAddress'] == '172.16.0.10') ||
            ($user['ipAddress'] == '192.168.2.50') ||($user['ipAddress'] == '10.1.1.100') ||($user['ipAddress'] == '172.17.0.5') || 
            ($user['ipAddress'] == '192.168.3.25') || ($user['ipAddress'] == '10.2.2.200') || ($user['ipAddress'] == '172.18.0.15') ||
            ($user['ipAddress'] == '192.168.4.75'))
            {
                $updatedData[] = [
                    'user_id'=> $user['user_id'],
                    'ipAddress' => $user['ipAddress'],
                    'totalTime' => $user['totalTime'],
                    'ipType' => 'Office'
                ];
            }
            else
            {
                $updatedData[] = [
                    'user_id'=> $user['user_id'],
                    'ipAddress' => $user['ipAddress'],
                    'totalTime' => $user['totalTime'],
                    'ipType' => 'Remote'
                ];
            }
        }

        return $this->mergingTimeForIP($updatedData);
    }

    public function mergingTimeForIP($resultData)
    {
        $ipCheck = [];

        foreach ($resultData as $entry) {
            $ipAddress = $entry['ipAddress'];
            $totalTime = $entry['totalTime'];
            $user_id = $entry['user_id'];
            $ipType = $entry['ipType'];
    
            $key = $user_id . '_' . $ipAddress;
    
            if (array_key_exists($key, $ipCheck)) {
                $ipCheck[$key]['totalTime'] += $totalTime;
            } else {
                $ipCheck[$key] = [
                    'user_id' => $user_id,
                    'ipAddress' => $ipAddress,
                    'totalTime' => $totalTime,
                    'ipType' => $ipType,
                ];
            }
        }

        $updatedData = array_values($ipCheck);

        return $this->mergingTimeForIPType($updatedData);
    }

    public function mergingTimeForIPType($resultData)
    {
        $ipCheck = [];

        foreach ($resultData as $entry) {
            $ipAddress = $entry['ipAddress'];
            $totalTime = $entry['totalTime'];
            $user_id = $entry['user_id'];
            $ipType = $entry['ipType'];

            $key = $user_id . '_' . $ipType;

            if (array_key_exists($key, $ipCheck)) 
            {
                $ipCheck[$key]['totalTime'] += $totalTime;
            } 
            else 
            {
                $ipCheck[$key] = [
                    'user_id' => $user_id,
                    'ipType' => $ipType,
                    'totalTime' => $totalTime,
                ];
            }
        }

        $updatedData = array_values($ipCheck); 

        return $this->combining($updatedData);
    }

    public function combining($resultData)
    {
        $users = [];

        foreach ($resultData as $entry) {
            $totalTime = $entry['totalTime'];
            $user_id = $entry['user_id'];
            $ipType = $entry['ipType'];

            if (array_key_exists($user_id, $users)) 
            {
                if ($ipType == 'Office') 
                {
                    $users[$user_id]['OfficeTime'] += $totalTime;
                } else 
                {
                    $users[$user_id]['RemoteTime'] += $totalTime;
                }
            } 
            else 
            {
                if ($ipType == 'Office') 
                {
                    $users[$user_id] = [
                        'user_id' => $user_id,
                        'OfficeTime' => $totalTime,
                        'RemoteTime' => 0
                    ];
                } 
                else 
                {
                    $users[$user_id] = [
                        'user_id' => $user_id,
                        'OfficeTime' => 0,
                        'RemoteTime' => $totalTime
                    ];
                }
            }
        }

        $updatedData = array_values($users);

        return $this->updateAttendence($updatedData);
    }

    public function updateAttendence($resultData)
    {
        foreach ($resultData as $entry) {
            $user_id = $entry['user_id'];
            $officeTime = $entry['OfficeTime'];
            $remoteTime = $entry['RemoteTime'];

            if($officeTime >= 300 )
            {
                $updatedData[] = [
                    'user_id'=> $user_id,
                    'OfficeTime' => $officeTime,
                    'RemoteTime' => $remoteTime,
                    'AttendenceStatus' => 'Present'
                ];
            } 
            else if($officeTime >= 180 && $officeTime < 300)
            {
                $updatedData[] = [
                    'user_id'=> $user_id,
                    'OfficeTime' => $officeTime,
                    'RemoteTime' => $remoteTime,
                    'AttendenceStatus' => 'Half-Day'
                ];
            }
            else
            {
                $updatedData[] = [
                    'user_id'=> $user_id,
                    'OfficeTime' => $officeTime,
                    'RemoteTime' => $remoteTime,
                    'AttendenceStatus' => 'Absent'
                ];

            }
        }

        return $this->store($updatedData);
    }

    public function store($finalize)
    {
        foreach ($finalize as $data) {
            EmployeeTime::create([
                'user_id'=>$data['user_id'],
                'remote_time'=>$data['RemoteTime'],
                'office_time'=>$data['OfficeTime'],
                'attendence_status'=>$data['AttendenceStatus'],
            ]);
        }
    }
}