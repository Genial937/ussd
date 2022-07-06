<?php

namespace App\Http\Controllers;

use App\Models\Point;
use App\Models\Purchase_item;
use App\Models\Transfer_point;
use App\Models\User;
use Illuminate\Http\Request;

class USSDController extends Controller
{
    public function ussd(Request $request)
    {
        header("content-type: text/plain");

        $session_id = $request->sessionId;
        $service_code = $request->serviceCode;
        $phone = $request->phoneNumber;
        $text = $request->text;

        $data = explode("*", $text);
        $level = 0;
        $level = count($data);

        if ($level == 0 || $level == 1 || $level == "") {
            $this->main_menu();
        }

        if ($level > 1) {
            switch ($data[1]) {
                case 1:
                    $this->check_user_exist($phone);
                    $this->customer_register($data, $phone);
                    break;

                case 2:
                    $this->check_password($data, $phone);
                    $this->transfer_points($data, $phone);
                    break;
                case 3:
                    $this->check_password($data, $phone);
                    $this->purchase_items($data, $phone);
                    break;
                case 4:
                    $this->check_password($data, $phone);
                    $this->check_points($data, $phone);
                    break;
                case 5:
                    $this->check_password($data, $phone);
                    $this->change_password($data, $phone);
                    break;
                default:
                    $text = "Invalid text input. <br> Please enter a valid input";
                    $this->ussd_stop($text);
                    break;
            }
        }
    }

    private function main_menu()
    {
        $text = "Welcome to Loyalty <br> Please reply with <br> 1. Register <br> 2. Transfer points <br> 3. Purchase item with points <br> 4. Check points balance <br> 5. Change password";

        $this->ussd_proceed($text);
    }

    private function ussd_proceed($text)
    {
        echo "CON " . $text;
    }

    private function ussd_stop($text)
    {
        echo "END " . $text;
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
            $text = "Please enter your National ID number";
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
        } else {
            return true;
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

    private function transfer_points($data, $phone)
    {
        //must have sufficient points
        //cannot transfer to your own account
        //should not transfer points less than 5 points
        //recipient must exists
        $available = $this->added_points($phone) - $this->used_points($phone);
        if (count($data) == 3) {
            $text = "Please enter the phone number \n you wish to transfer to";
            $this->ussd_proceed($text);
        }

        if (count($data) == 4) {
            $this->check_user($data[3]);
        }

        if (count($data) == 4) {
            $text = "Please enter the amount of points \n you wish to transfer";
            $this->ussd_proceed($text);
        }

        if (count($data) == 5) {
            $to = $data[3];
            $from = $phone;
            $points = $data[4];
            $ref = "TPL" . $this->reference_no(9);

            if ($points < 5) {
                $text = "You cannot transfer less than 5 points";
                $this->ussd_stop($text);
                exit();
            } elseif ($points > $available) {
                $text = "You have insufficient points balance. \n your point balance is $available";
                $this->ussd_stop($text);
                exit();
            } elseif ($to == $from) {
                $text = "You cannot transfer points to your own account";
                $this->ussd_stop($text);
                exit();
            } else {
                $data = [
                    'from_phone_number' => $from,
                    'to_phone_number' => $to,
                    'points' => $points,
                    'reference_no' => $ref,
                ];
                $query = Transfer_point::create(array_merge($data));
                if ($query) {
                    $text = "You have successfully transfered $points points to $to";
                    $this->ussd_stop($text);
                    exit();
                } else {
                    $text = "Oops! Something went wrong! Please try again";
                    $this->ussd_stop($text);
                    exit();
                }
            }
        }
    }

    private function purchase_items($data, $phone)
    {
        $available = $this->added_points($phone) - $this->used_points($phone);
        if (count($data) == 3) {
            $text = "Please enter the amount of points you want to use";
            $this->ussd_proceed($text);
        }

        if (count($data) == 4) {
            $points = $data[3];
            $ref = "PIW" . $this->reference_no(7);
            if ($points > $available) {
                $text = "You have insufficient points. \n your points balance is $available";
                $this->ussd_stop($text);
                exit();
            } else {
                $data = [
                    'phone' => $phone,
                    'points' => $points,
                    'reference_number' => $ref,
                ];
                $transaction = Purchase_item::create(array_merge($data));
                if ($transaction) {
                    $text = "You have successfully purchased an item using $points points";
                    $this->ussd_stop($text);
                    exit();
                } else {
                    $text = "Something went wrong! Please try again later.";
                    $this->ussd_stop($text);
                    exit();
                }
            }
        }
    }

    private function change_password($data, $phone)
    {
        if (count($data) == 3) {
            $text = "Enter new password";
            $this->ussd_proceed($text);
        }

        if (count($data) == 4) {
            $check = User::where([['phone', $phone], ['password', $data[3]]])->first();
            if ($check) {
                $update = $check->update(['password' => $data[3]]);
                if ($update) {
                    $text = "You have successfully changed you password! \n New password is $data[3]";
                    $this->ussd_stop($text);
                    exit();
                } else {
                    $text = "Something went wrong!";
                    $this->ussd_stop($text);
                    exit();
                }
            } else {
                $text = "You have entered a wrong password!";
                $this->ussd_stop($text);
                exit();
            }
        }
    }

    private function check_points($data, $phone)
    {
        if (count($data) == 3) {
            $points = $this->added_points($phone) - $this->used_points($phone);
            $text = "Your point balance is $points";
            $this->ussd_stop($text);
        }
    }

    private function added_points($phone)
    {
        $added = Point::where('phone', $phone)->get()->sum('points');
        $received = Transfer_point::where('to_phone_number', $phone)->get()->sum('points');
        $added_points = $added + $received;
        return $added_points;
    }

    private function used_points($phone)
    {
        $purchase_items = Purchase_item::where('phone', $phone)->get()->sum('points');
        $transfered_points = Transfer_point::where('from_phone_number', $phone)->get()->sum('points');
        $used_points = $purchase_items + $transfered_points;
        return $used_points;
    }

    private function reference_no($length)
    {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle($chars), 0, $length);
    }

    private function check_user($phone)
    {
        $check = User::where('phone', $phone)->first();
        if ($check) {
            return true;
        } else {
            $text = "This phone number is not registered";
            $this->ussd_stop($text);
            exit();
        }
    }
}