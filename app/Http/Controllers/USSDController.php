<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class USSDController extends Controller
{
    public function ussd($phone, $text)
    {
        header("content-type: text/plain");

        // $session_id = $request->sessionId;
        // $service_code = $request->serviceCode;
        $phone = $phone;
        $text = $text;

        $data = explode("*", $text);
        $level = 0;
        $level = count($data);

        if ($level == 0 || $level == 1) {
            $this->main_menu();
        }

        if ($level > 1) {
            switch ($data[1]) {
                case 1:
                    $this->check_user_exist($phone);
                    $this->customer_register($data, $phone);
                    break;

                case 2:
                    $this->transfer_points();
                    break;
                case 3:
                    $this->purchase_items();
                    break;
                case 4:
                    $this->check_password($data, $phone);
                    $this->check_points($data);
                    break;
                default:
                    $text = "Invalid text input. <br> Please enter a valid input";
                    $this->ussd_stop($text);
            }
        }
    }

    private function main_menu()
    {
        $text = "Welcome to Loyalty <br> Please reply with <br> 1. Register <br> 2. Transfer points <br> 3. Purchase item with points <br> 4. Check points balance";

        $this->ussd_proceed($text);
    }

    private function ussd_proceed($text)
    {
        echo "CON" . $text;
    }

    private function ussd_stop($text)
    {
        echo "END" . $text;
    }

    private function customer_register($data, $phone)
    {
        #firstname, middlename, lastname,email, id_number,gender, password
        if (count($data) == 2) {
            $text = "Please enter your first name";
            $this->ussd_proceed($text);
        }
        if (count($data) == 3) {
            $text = "Please enter your middle name";
            $this->ussd_proceed($text);
        }
        if (count($data) == 4) {
            $text = "Please enter your last name";
            $this->ussd_proceed($text);
        }
        if (count($data) == 5) {
            $text = "Please enter your email";
            $this->ussd_proceed($text);
        }
        if (count($data) == 6) {
            $text = "Please enter your gender (Male or Female)";
            $this->ussd_proceed($text);
        }
        if (count($data) == 7) {
            $text = "Please enter your id number";
            $this->ussd_proceed($text);
        }
        if (count($data) == 8) {
            $text = "Please enter your password";
            $this->ussd_proceed($text);
        }
        if (count($data) == 9) {
            $phone  = $phone;
            $first_name = $data[2];
            $middle_name = $data[3];
            $last_name = $data[4];
            $email = $data[5];
            $gender = $data[6];
            $id_number = $data[7];
            $password = $data[8];
            $user_data = [
                'phone' => $phone,
                'first_name' => $first_name,
                'middle_name' => $middle_name,
                'last_name' => $last_name,
                'email' => $email,
                'gender' => $gender,
                'id_number' => $id_number,
                'password' => $password,
            ];

            User::create(array_merge($user_data));

            $text = "Dear, " . $first_name . ", Thank you for registering";
            $this->ussd_stop($text);
        }
    }

    private function check_user_exist($phone)
    {
        $check = User::where('phone', $phone)->first();
        if ($check) {
            $text = 'User with this phone ' . $phone . ' already exists';
            $this->ussd_stop($text);
            exit();
        }
    }

    private function check_password($data, $phone)
    {
        if (count($data) == 2) {
            $text = "Please enter your password";
            $this->ussd_proceed($text);
        }

        if (count($data) == 3) {
            $password = $data[2];
            $check = User::where([['password', $password], ['phone', $phone]])->first();
            if (!$check) {
                $text = "Invalid Password";
                $this->ussd_stop($text);
                exit();
            }
            return true;
        }
    }

    private function transfer_points()
    {
        $text = "You have transfered 10 points to 009099090";
        $this->ussd_proceed($text);
    }

    private function purchase_items()
    {
        $text = "You have purchased items for 5 points";
        $this->ussd_proceed($text);
    }

    private function check_points($data)
    {
        if (count($data) == 3) {
            $text = "Your point balance is 100";
            $this->ussd_proceed($text);
        }
    }
}