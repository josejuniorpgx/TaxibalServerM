<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DriverDocument;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\DriverResource;
use Illuminate\Support\Facades\Password;
use App\Models\AppSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\DriverRequest;

class UserController extends Controller
{
    public function register(UserRequest $request)
    {
        $input = $request->all();

        $password = $input['password'];
        $input['user_type'] = isset($input['user_type']) ? $input['user_type'] : 'rider';
        $input['password'] = Hash::make($password);

        if( in_array($input['user_type'],['driver']))
        {
            $input['status'] = isset($input['status']) ? $input['status']: 'pending';
        }

        $input['display_name'] = $input['first_name']." ".$input['last_name'];
        $input['last_actived_at'] = now();
        $user = User::create($input);
        $user->assignRole($input['user_type']);

        if( $request->has('user_detail') && $request->user_detail != null ) {
            $user->userDetail()->create($request->user_detail);
        }

        $message = __('message.save_form',['form' => __('message.'.$input['user_type']) ]);
        $user->api_token = $user->createToken('auth_token')->plainTextToken;
        $user->profile_image = getSingleMedia($user, 'profile_image', null);
        $response = [
            'message' => $message,
            'data' => $user
        ];
        return json_custom_response($response);
    }

    public function driverRegister(DriverRequest $request)
    {
        $input = $request->all();
        $password = $input['password'];
        $input['user_type'] = isset($input['user_type']) ? $input['user_type'] : 'driver';
        $input['password'] = Hash::make($password);

        $input['status'] = isset($input['status']) ? $input['status']: 'pending';

        $input['display_name'] = $input['first_name']." ".$input['last_name'];
        $input['is_available'] = 1;
        $input['last_actived_at'] = now();
        $user = User::create($input);
        $user->assignRole($input['user_type']);

        if( $request->has('user_detail') && $request->user_detail != null ) {
            $user->userDetail()->create($request->user_detail);
        }

        if( $request->has('user_bank_account') && $request->user_bank_account != null ) {
            $user->userBankAccount()->create($request->user_bank_account);
        }
        $user->userWallet()->create(['total_amount' => 0 ]);

        $message = __('message.save_form',['form' => __('message.driver') ]);
        $user->api_token = $user->createToken('auth_token')->plainTextToken;
        $user->is_verified_driver = (int) $user->is_verified_driver;// DriverDocument::verifyDriverDocument($user->id);
        $user->profile_image = getSingleMedia($user, 'profile_image', null);
        $response = [
            'message' => $message,
            'data' => $user
        ];
        return json_custom_response($response);
    }

    public function login(Request $request)
    {
        if(Auth::attempt(['email' => request('email'), 'password' => request('password'), 'user_type' => request('user_type')])){

            $user = Auth::user();

            if( $user->status == 'banned' ) {
                $message = __('message.account_banned');
                return json_message_response($message,400);
            }

            if(request('player_id') != null){
                $user->player_id = request('player_id');
            }

            if(request('fcm_token') != null){
                $user->fcm_token = request('fcm_token');
            }
            $user->last_actived_at = now();
            $user->save();

            $success = $user;
            $success['api_token'] = $user->createToken('auth_token')->plainTextToken;
            $success['profile_image'] = getSingleMedia($user,'profile_image',null);
            $is_verified_driver = false;
            if($user->user_type == 'driver') {
                $is_verified_driver = $user->is_verified_driver; // DriverDocument::verifyDriverDocument($user->id);
            }
            $success['is_verified_driver'] = (int) $is_verified_driver;
            unset($success['media']);

            return json_custom_response([ 'data' => $success ], 200 );
        }
        else{
            $message = __('auth.failed');

            return json_message_response($message,400);
        }
    }

    public function userList(Request $request)
    {
        $user_type = isset($request['user_type']) ? $request['user_type'] : 'rider';

        $user_list = User::query();

        $user_list->when(request('user_type'), function ($q) use($user_type) {
            return $q->where('user_type', $user_type);
        });

        $user_list->when(request('fleet_id'), function ($q) {
            return $q->where('fleet_id', request('fleet_id'));
        });

        if( $request->has('is_online') && isset($request->is_online) )
        {
            $user_list = $user_list->where('is_online',request('is_online'));
        }

        if( $request->has('status') && isset($request->status) )
        {
            $user_list = $user_list->where('status',request('status'));
        }

        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page))
        {
            if(is_numeric($request->per_page)){
                $per_page = $request->per_page;
            }
            if($request->per_page == -1 ){
                $per_page = $user_list->count();
            }
        }

        $user_list = $user_list->paginate($per_page);

        if( $user_type == 'driver' ) {
            $items = DriverResource::collection($user_list);
        } else {
            $items = UserResource::collection($user_list);
        }

        $response = [
            'pagination' => json_pagination_response($items),
            'data' => $items,
        ];

        return json_custom_response($response);
    }

    public function userDetail(Request $request)
    {
        $id = $request->id;

        $user = User::where('id',$id)->first();
        if(empty($user))
        {
            $message = __('message.user_not_found');
            return json_message_response($message,400);
        }

        $response = [
            'data' => null,
        ];
        if( $user->user_type == 'driver') {
            $user_detail = new DriverResource($user);

            $response = [
                'data' => $user_detail,
                'required_document' => driver_required_document($user),
            ];
        } else {
            $user_detail = new UserResource($user);
            $response = [
                'data' => $user_detail
            ];
        }

        return json_custom_response($response);

    }

    public function changePassword(Request $request){
        $user = User::where('id',Auth::user()->id)->first();

        if($user == "") {
            $message = __('message.user_not_found');
            return json_message_response($message,400);
        }

        $hashedPassword = $user->password;

        $match = Hash::check($request->old_password, $hashedPassword);

        $same_exits = Hash::check($request->new_password, $hashedPassword);
        if ($match)
        {
            if($same_exits){
                $message = __('message.old_new_pass_same');
                return json_message_response($message,400);
            }

			$user->fill([
                'password' => Hash::make($request->new_password)
            ])->save();

            $message = __('message.password_change');
            return json_message_response($message,200);
        }
        else
        {
            $message = __('message.valid_password');
            return json_message_response($message,400);
        }
    }

    public function updateProfile(UserRequest $request)
    {
        $user = Auth::user();
        if($request->has('id') && !empty($request->id)){
            $user = User::where('id',$request->id)->first();
        }
        if($user == null){
            return json_message_response(__('message.no_record_found'),400);
        }

        $user->fill($request->all())->update();

        if(isset($request->profile_image) && $request->profile_image != null ) {
            $user->clearMediaCollection('profile_image');
            $user->addMediaFromRequest('profile_image')->toMediaCollection('profile_image');
        }

        $user_data = User::find($user->id);

        if($user_data->userDetail != null && $request->has('user_detail') ) {
            $user_data->userDetail->fill($request->user_detail)->update();
        } else if( $request->has('user_detail') && $request->user_detail != null ) {
            $user_data->userDetail()->create($request->user_detail);
        }

        if($user_data->userBankAccount != null && $request->has('user_bank_account')) {
            $user_data->userBankAccount->fill($request->user_bank_account)->update();
        } else if( $request->has('user_bank_account') && $request->user_bank_account != null ) {
            $user_data->userBankAccount()->create($request->user_bank_account);
        }

        $message = __('message.updated');
        // $user_data['profile_image'] = getSingleMedia($user_data,'profile_image',null);
        unset($user_data['media']);

        if( $user_data->user_type == 'driver') {
            $user_resource = new DriverResource($user_data);
        } else {
            $user_resource = new UserResource($user_data);
        }

        $response = [
            'data' => $user_resource,
            'message' => $message
        ];
        return json_custom_response( $response );
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if($request->is('api*')){
            $clear = request('clear');
            if( $clear != null ) {
                $user->$clear = null;
            }
            $user->save();
            return json_message_response('Logout successfully');
        }
    }

    public function forgetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $response = Password::sendResetLink(
            $request->only('email')
        );

        return $response == Password::RESET_LINK_SENT
            ? response()->json(['message' => __($response), 'status' => true], 200)
            : response()->json(['message' => __($response), 'status' => false], 400);
    }

    public function socialLogin(Request $request)
    {
        $input = $request->all();

        \Log::info('=== SOCIAL LOGIN START ===', [
            'login_type' => $input['login_type'] ?? 'null',
            'username' => $input['username'] ?? 'null',
            'email' => $input['email'] ?? 'null',
            'firebase_uid' => $input['firebase_uid'] ?? 'null',
            'uid' => $input['uid'] ?? 'null',
        ]);

        $user_data = null;

        if($input['login_type'] === 'mobile'){
            \Log::info('=== MOBILE LOGIN FLOW ===');

            // PASO 1: Buscar por username + login_type = mobile
            $user_data = User::where('username', $input['username'])
                ->where('login_type','mobile')
                ->first();

            \Log::info('Step 1 - Search by username+mobile:', [
                'found' => $user_data ? 'YES' : 'NO',
                'user_id' => $user_data->id ?? 'null'
            ]);

            // PASO 2: Si no lo encuentra, buscar por Firebase UID
            if(!$user_data) {
                $firebase_uid = $input['firebase_uid'] ?? $input['uid'] ?? null;

                if(!empty($firebase_uid)) {
                    \Log::info('Step 2 - Searching by Firebase UID:', ['uid' => $firebase_uid]);

                    $user_data = User::where('uid', $firebase_uid)->first();

                    if($user_data) {
                        \Log::info('Step 2 - Found user by UID:', [
                            'user_id' => $user_data->id,
                            'current_login_type' => $user_data->login_type,
                            'current_username' => $user_data->username
                        ]);

                        // Usuario existe pero con diferente login_type, actualizar a mobile
                        $user_data->update([
                            'login_type' => 'mobile',
                            'username' => $input['username']
                        ]);

                        \Log::info('User updated to mobile login_type');
                    } else {
                        \Log::info('Step 2 - No user found by UID');
                    }
                } else {
                    \Log::warning('No Firebase UID provided for mobile login');
                }
            }

            // PASO 3: Si aún no encuentra, buscar por contact_number como fallback
            if(!$user_data && !empty($input['contact_number'])) {
                \Log::info('Step 3 - Searching by contact_number:', ['contact' => $input['contact_number']]);

                $user_data = User::where('contact_number', $input['contact_number'])->first();

                if($user_data) {
                    \Log::info('Step 3 - Found user by contact_number:', [
                        'user_id' => $user_data->id,
                        'updating_to_mobile' => true
                    ]);

                    // Actualizar para mobile login
                    $user_data->update([
                        'login_type' => 'mobile',
                        'username' => $input['username'],
                        'uid' => $input['firebase_uid'] ?? $input['uid']
                    ]);
                }
            }

        } else {
            \Log::info('=== NON-MOBILE LOGIN FLOW ===');

            // NUEVO: Buscar primero por uid de Firebase
            $firebase_uid = $input['firebase_uid'] ?? $input['uid'] ?? null;
            if (!empty($firebase_uid)) {
                \Log::info('Searching by Firebase UID:', ['uid' => $firebase_uid]);
                $user_data = User::where('uid', $firebase_uid)->first();

                if($user_data) {
                    \Log::info('Found user by Firebase UID:', ['user_id' => $user_data->id]);
                }
            }

            // Si no se encontró por firebase_uid, buscar por email
            if (!$user_data && !empty($input['email'])) {
                \Log::info('Searching by email:', ['email' => $input['email']]);
                $user_data = User::where('email', $input['email'])->first();

                if($user_data) {
                    \Log::info('Found user by email:', ['user_id' => $user_data->id]);
                }
            }
        }

        \Log::info('=== USER SEARCH COMPLETED ===', [
            'user_found' => $user_data ? 'YES' : 'NO',
            'user_id' => $user_data->id ?? 'null',
            'user_login_type' => $user_data->login_type ?? 'null'
        ]);

        if( $user_data != null ) {
            \Log::info('=== EXISTING USER LOGIN ===', ['user_id' => $user_data->id]);

            if( !in_array($user_data->user_type, ['admin',request('user_type')] )) {
                $message = __('auth.failed');
                \Log::warning('User type mismatch', [
                    'user_type' => $user_data->user_type,
                    'requested_type' => request('user_type')
                ]);
                return json_message_response($message,400);
            }

            if( $user_data->status == 'banned' ) {
                $message = __('message.account_banned');
                \Log::warning('Banned user attempted login', ['user_id' => $user_data->id]);
                return json_message_response($message,400);
            }

            // MODIFICADO: Lógica para linking de cuentas
            if( !isset($user_data->login_type) || $user_data->login_type == '' )
            {
                // Si la cuenta no tiene login_type, pero es linking de cuenta
                if (!empty($input['uid']) && empty($user_data->uid)) {
                    // Actualizar la cuenta existente con el uid de Firebase
                    $this->updateUserForLinking($user_data, $input);
                    $message = __('message.login_success');
                    \Log::info('Account linked successfully');
                } else {
                    // Lógica original para cuentas no vinculadas
                    if($request->login_type === 'google')
                    {
                        $message = __('validation.unique',['attribute' => 'email' ]);
                    } else {
                        $message = __('validation.unique',['attribute' => 'username' ]);
                    }
                    \Log::warning('Account linking failed - unique constraint');
                    return json_message_response($message,400);
                }
            } else {
                // NUEVO: Si ya tiene login_type, verificar si es linking de métodos
                if (!empty($input['uid']) && $user_data->uid === $input['uid']) {
                    // Es el mismo usuario con diferentes métodos de autenticación
                    $this->updateUserLoginMethod($user_data, $input);
                    $message = __('message.login_success');
                    \Log::info('Login method updated');
                } else {
                    $message = __('message.login_success');
                    \Log::info('Standard login successful');
                }
            }
        } else {
            \Log::info('=== NEW USER FLOW ===');

            // Usuario no existe - verificar si es mobile para retornar respuesta especial
            if($request->login_type === 'mobile'){
                \Log::info('Mobile user does not exist, returning false');
                $otp_response = [
                    'status' => true,
                    'is_user_exist' => false
                ];
                return json_custom_response($otp_response);
            }

            // Para otros tipos de login, continuar con creación de usuario
            if($request->login_type === 'google')
            {
                $key = 'email';
                $value = $request->email;
            } else {
                $key = 'username';
                $value = $request->username;
            }

            // MODIFICADO: Validación más inteligente para linking
            $validation_rules = [];

            // Verificar si es un caso de linking (usuario existe por email pero sin uid)
            $existing_user_by_email = User::where('email', $input['email'])->first();
            $is_linking_case = $existing_user_by_email && empty($existing_user_by_email->uid) && !empty($input['uid']);

            if (!$is_linking_case) {
                // Solo validar unicidad si NO es caso de linking
                $validation_rules['email'] = 'required|email|unique:users,email';
                $validation_rules['username'] = 'required|unique:users,username';
                $validation_rules['contact_number'] = 'max:20|unique:users,contact_number';
            } else {
                // Para linking, solo validar formato
                $validation_rules['email'] = 'required|email';
                if (isset($input['username'])) {
                    $validation_rules['username'] = 'required';
                }
                if (isset($input['contact_number'])) {
                    $validation_rules['contact_number'] = 'max:20';
                }
            }

            $validator = Validator::make($input, $validation_rules);

            if ( $validator->fails() ) {
                \Log::warning('Validation failed', ['errors' => $validator->errors()]);
                $data = [
                    'status' => false,
                    'message' => $validator->errors()->first(),
                    'all_message' =>  $validator->errors()
                ];

                return json_custom_response($data, 422);
            }

            $password = !empty($input['accessToken']) ? $input['accessToken'] : $input['email'];

            $input['display_name'] = $input['first_name']." ".$input['last_name'];
            $input['password'] = Hash::make($password);
            $input['user_type'] = isset($input['user_type']) ? $input['user_type'] : 'rider';

            // NUEVO: Agregar uid de Firebase si viene en el request
            if (!empty($input['firebase_uid'])) {
                $input['uid'] = $input['firebase_uid'];
            } elseif (!empty($input['uid'])) {
                $input['uid'] = $input['uid'];
            }

            \Log::info('Creating new user', [
                'user_type' => $input['user_type'],
                'has_uid' => !empty($input['uid'])
            ]);

            $user = User::create($input);
            if($user->userWallet == null) {
                $user->userWallet()->create(['total_amount' => 0 ]);
            }
            $user->assignRole($input['user_type']);

            $user_data = User::where('id',$user->id)->first();
            $message = __('message.save_form',['form' => $input['user_type'] ]);

            \Log::info('New user created successfully', ['user_id' => $user->id]);
        }

        // Preparar respuesta final
        \Log::info('=== PREPARING RESPONSE ===', [
            'user_id' => $user_data->id,
            'message' => $message
        ]);

        $user_data['api_token'] = $user_data->createToken('auth_token')->plainTextToken;
        $user_data['profile_image'] = getSingleMedia($user_data, 'profile_image', null);

        $is_verified_driver = false;
        if($user_data->user_type == 'driver') {
            $is_verified_driver = $user_data->is_verified_driver;
        }
        $user_data['is_verified_driver'] = (int) $is_verified_driver;

        $response = [
            'status' => true,
            'message' => $message,
            'data' => $user_data
        ];

        \Log::info('=== SOCIAL LOGIN SUCCESS ===', [
            'user_id' => $user_data->id,
            'login_type' => $user_data->login_type
        ]);

        return json_custom_response($response);
    }

    /**
     * Actualiza usuario existente para linking de cuentas
     */
    private function updateUserForLinking($user, $input)
    {
        $updateData = [];

        if (!empty($input['uid'])) {
            $updateData['uid'] = $input['uid'];
        }

        if (!empty($input['firebase_uid'])) {
            $updateData['uid'] = $input['firebase_uid'];
        }

        if (!empty($input['login_type'])) {
            $updateData['login_type'] = $input['login_type'];
        }

        if (!empty($updateData)) {
            $user->update($updateData);
            \Log::info('User linking data updated', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($updateData)
            ]);
        }
    }

    /**
     * Actualiza método de login para usuario existente
     */
    private function updateUserLoginMethod($user, $input)
    {
        $updateData = [];

        // Si viene un nuevo login_type, actualizar
        if (!empty($input['login_type']) && $user->login_type !== $input['login_type']) {
            $updateData['login_type'] = $input['login_type'];
        }

        // Actualizar username si es mobile y no lo tenía
        if ($input['login_type'] === 'mobile' && empty($user->username) && !empty($input['username'])) {
            $updateData['username'] = $input['username'];
        }

        if (!empty($updateData)) {
            $user->update($updateData);
            \Log::info('User login method updated', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($updateData)
            ]);
        }
    }







    public function updateUserStatus(Request $request)
    {
        $user_id = $request->id ?? auth()->user()->id;

        $user = User::where('id',$user_id)->first();

        if($user == "") {
            $message = __('message.user_not_found');
            return json_message_response($message,400);
        }
        if($request->has('status')) {
            $user->status = $request->status;
        }
        if($request->has('is_online')) {
            $user->is_online = $request->is_online;
        }
        // if($request->has('is_available')) {
        //     $user->is_available = $request->is_available;
        // }
        if($request->has('latitude')) {
            $user->latitude = $request->latitude;
        }
        if($request->has('longitude')) {
            $user->longitude = $request->longitude;
        }
        if($request->has('latitude') && $request->has('longitude') ) {
            $user->last_location_update_at = date('Y-m-d H:i:s');
        }
        if($request->has('player_id')) {
            $user->player_id = $request->player_id;
        }
        if($request->has('app_version')) {
            $user->app_version = $request->app_version;
        }

        if($request->has('otp_verify_at')) {
            $user->otp_verify_at = $request->otp_verify_at;
        }

        if($request->is_online == 1) {
            $user->is_available = 1;
        }
        $user->save();
        /*
        if( $user->user_type == 'driver') {
            $user_resource = new DriverResource($user);
        } else {
            $user_resource = new UserResource($user);
        }*/
        $user_resource = null;
        $message = __('message.update_form',['form' => __('message.status') ]);
        $response = [
            'data' => $user_resource,
            'message' => $message
        ];
        return json_custom_response($response);
    }

    public function updateAppSetting(Request $request)
    {
        $data = $request->all();
        AppSetting::updateOrCreate(['id' => $request->id],$data);
        $message = __('message.save_form',['form' => __('message.app_setting') ]);
        $response = [
            'data' => AppSetting::first(),
            'message' => $message
        ];
        return json_custom_response($response);
    }

    public function getAppSetting(Request $request)
    {
        if($request->has('id') && isset($request->id)){
            $data = AppSetting::where('id',$request->id)->first();
        } else {
            $data = AppSetting::first();
        }

        return json_custom_response($data);
    }

    public function deleteUserAccount(Request $request)
    {
        $id = auth()->id();
        $user = User::where('id', $id)->first();
        $message = __('message.not_found_entry',['name' => __('message.account') ]);

        if( $user != '' ) {
            $user->delete();
            $message = __('message.account_deleted');
        }

        return json_custom_response(['message'=> $message, 'status' => true]);
    }
}
