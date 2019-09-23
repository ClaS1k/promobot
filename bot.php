<?php 

include('connectdb.php');

if (!isset($_REQUEST)) { 
return; 
} 


$confirmation_token = 'be0a88ff'; 

$token = '55e07756d47cc49eb43788a4be8905458a381b3f95646a4e0ab1cd71204887c6f7e2b442dd85b9a374867'; 

$data = json_decode(file_get_contents('php://input')); 

switch ($data->type) { 

case 'confirmation': 
    echo $confirmation_token; 
break; 

case 'message_new':
    echo('ok');
    $user_id = $data->object->from_id;  
    $msg=$data->object->text;
    $msg_id=$data->object->id;
    $random_id=$user_id."1".$msg_id;
    $user_info = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$user_id}&access_token={$token}&v=5.0")); 
    $user_name = $user_info->response[0]->first_name;
    $btn_payload = json_decode($data->object->payload);
    $action = $btn_payload->command;
    $sql="SELECT `stage` FROM `users` WHERE `id`=$user_id AND `roles`=5";
    $result=mysqli_query($dbc, $sql);
    if (mysqli_num_rows($result)!=0){
        $row=mysqli_fetch_array($result, MYSQLI_ASSOC);
        switch ($action){
            case 'setRole':
                $vk_id=$btn_payload->id;
                $level=$btn_payload->level;
                $sql="UPDATE `users` SET `roles`='$level' WHERE `id`='$vk_id'";
                mysqli_query($dbc, $sql);
                $keyboard=array(
                    'one_time'=>false,
                    'buttons'=>array(
                        array(
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>'{"command":"CheckRequests"}',
                                    'label'=>'Проверка заявок'
                                ),
                                'color'=>'primary'
                            ),
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>'{"command":"GetAncet"}',
                                    'label'=>'Получить анкету'
                                ),
                                'color'=>'primary'
                            )
                        )
                    )
                );              
                $request_params = array( 
                    'message' => "Роль установлена!", 
                    'peer_id' => $user_id, 
                    'access_token' => $token,
                    'keyboard' => json_encode($keyboard),
                    'random_id'=>$random_id,
                    'v' => '5.80'
                );  
                $get_params=http_build_query($request_params);
                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
            break;
            case 'SetRoleLevel':
                $vk_id=$btn_payload->id;
                $keyboard=array(
                    'one_time'=>true,
                    'buttons'=>array(
                        array(
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>"{\"command\":\"setRole\",\"id\":\"$vk_id\", \"level\":\"1\"}",
                                    'label'=>'Промоутер'
                                ),
                                'color'=>'secondary'
                            ),
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>"{\"command\":\"setRole\",\"id\":\"$vk_id\", \"level\":\"2\"}",
                                    'label'=>'Супервайзер'
                                ),
                                'color'=>'secondary'
                            )
                        ),
                        array(
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>"{\"command\":\"setRole\",\"id\":\"$vk_id\", \"level\":\"3\"}",
                                    'label'=>'Заказчик'
                                ),
                                'color'=>'primary'
                            ),
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>"{\"command\":\"setRole\",\"id\":\"$vk_id\", \"level\":\"4\"}",
                                    'label'=>'Координатор'
                                ),
                                'color'=>'primary'
                            )
                        ),
                        array(
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>"{\"command\":\"setRole\",\"id\":\"$vk_id\", \"level\":\"5\"}",
                                    'label'=>'Основатель'
                                ),
                                'color'=>'negative'
                            ),
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>"{\"command\":\"toStart\"}",
                                    'label'=>'В начало'
                                ),
                                'color'=>'negative'
                            )
                        )    
                    )
                ); 
                $request_params = array( 
                    'message' => "Выберите роль", 
                    'peer_id' => $user_id, 
                    'access_token' => $token,
                    'keyboard' => json_encode($keyboard),
                    'random_id'=>$random_id.'749',
                    'v' => '5.80'
                );  
                $get_params=http_build_query($request_params);
                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                exit();  
            break;
            case 'CheckRequests':
                $sql="SELECT * FROM `promos` WHERE `photo` IS NOT NULL AND `full_name` IS NOT NULL AND `id` IS NOT NULL AND `gender` IS NOT NULL AND `summary` IS NOT NULL AND `confirmed`='0' LIMIT 1";
                $result=mysqli_query($dbc, $sql);
                if (mysqli_num_rows($result)==0){
                    $request_params = array( 
                        'message' => 'На данный момент не подтверждённых заявок нет!', 
                        'peer_id' => $user_id, 
                        'access_token' => $token,
                        'random_id'=>$random_id,
                        'v' => '5.80'
                    ); 
                    $get_params = http_build_query($request_params); 
                    file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                    exit();
                }else{
                    $row=mysqli_fetch_array($result, MYSQLI_ASSOC);
                    $vk_id=$row['id'];
                    $full_name=$row['full_name'];
                    $gender=$row['gender'];
                    $summary=$row['summary'];
                    $url=$row['photo']; 

                    if ($gender==0){
                        $gender="Женский";
                    }else{
                        $gender="Мужской";
                    }

                    $request_params=array(
                        'peer_id'=>$user_id,
                        'access_token'=>$token,
                        'v'=>'5.80'
                    );
                    $get_params=http_build_query($request_params);
                    $messages_upload_server=json_decode(file_get_contents('https://api.vk.com/method/photos.getMessagesUploadServer?'. $get_params));
                    $messages_upload_server=$messages_upload_server->response->upload_url;

                    $curl = curl_init($messages_upload_server);
                    curl_setopt($curl, CURLOPT_POST, true);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, array('file' => new CURLfile($url)));
                    $json = curl_exec($curl);
                    curl_close($curl);

                    $upload_result=json_decode($json);

                    $photos=$upload_result->photo;
                    $servers=$upload_result->server;
                    $hash=$upload_result->hash;
                
                    $request_params=array(
                        'photo' => $photos,
                        'server' => $servers,
                        'hash' => $hash,
                        'access_token' => $token,
                        'v'=>'5.80'
                    );
                    $get_params=http_build_query($request_params);
                    $photo=json_decode(file_get_contents('https://api.vk.com/method/photos.saveMessagesPhoto?'. $get_params));                    

                    $photo_id=$photo->response[0]->id;
                    $owner_id=$photo->response[0]->owner_id;
                    $photo="photo".$owner_id."_".$photo_id;
                    $keyboard=array(
                        'one_time'=>true,
                        'buttons'=>array(
                            array(
                                array(
                                    'action'=>array(
                                        'type'=>'text',
                                        'payload'=>"{\"command\":\"AcceptRequest\",\"id\":\"$vk_id\"}",
                                        'label'=>'Одобрить заявку'
                                    ),
                                    'color'=>'positive'
                                ),
                                array(
                                    'action'=>array(
                                        'type'=>'text',
                                        'payload'=>"{\"command\":\"DecineRequest\",\"id\":\"$vk_id\"}",
                                        'label'=>'Отклонить заявку'
                                    ),
                                    'color'=>'negative'
                                ),
                                array(
                                    'action'=>array(
                                        'type'=>'text',
                                        'payload'=>'{"command":"toStart"}',
                                        'label'=>'В начало'
                                    ),
                                    'color'=>'primary'
                                )
                            )
                        )
                    );              
                    $request_params = array( 
                        'message' => "Имя: $full_name, Пол: $gender, Резюме: \n $summary", 
                        'peer_id' => $user_id, 
                        'access_token' => $token,
                        'attachment'=>$photo,
                        'keyboard' => json_encode($keyboard),
                        'random_id'=>$random_id,
                        'v' => '5.80'
                    );  
                    $get_params=http_build_query($request_params);
                    file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                    exit();
                }
            break;
            case 'AcceptRequest':
                $subject_id=$btn_payload->id;
                $sql="UPDATE `promos` SET `confirmed`='1' WHERE `id`='$subject_id'";
                mysqli_query($dbc, $sql);
                $request_params = array( 
                    'message' => "Ваша анкета одобрена!", 
                    'peer_id' => $subject_id, 
                    'access_token' => $token,
                    'random_id'=>$random_id,
                    'v' => '5.80'
                ); 
                $get_params = http_build_query($request_params); 
                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                $keyboard=array(
                    'one_time'=>false,
                    'buttons'=>array(
                        array(
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>'{"command":"CheckRequests"}',
                                    'label'=>'Проверка заявок'
                                ),
                                'color'=>'primary'
                            ),
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>'{"command":"GetAncet"}',
                                    'label'=>'Получить анкету'
                                ),
                                'color'=>'primary'
                            )
                        )
                    )
                );              
                $request_params = array( 
                    'message' => "Рады видеть вас, ".$user_name."!", 
                    'peer_id' => $user_id, 
                    'access_token' => $token,
                    'keyboard' => json_encode($keyboard),
                    'random_id'=>$random_id,
                    'v' => '5.80'
                ); 
                $get_params = http_build_query($request_params); 
                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                exit();
            break;
            case 'DecineRequest':
                $subject_id=$btn_payload->id;
                $sql="DELETE FROM `promos` WHERE `id`='$subject_id'";
                mysqli_query($dbc, $sql);
                $sql="DELETE FROM `users` WHERE `id`='$subject_id'";
                mysqli_query($dbc, $sql);
                $request_params = array( 
                    'message' => "Ваша анкета отклонена!", 
                    'peer_id' => $subject_id, 
                    'access_token' => $token,
                    'random_id'=>$random_id,
                    'v' => '5.80'
                ); 
                $get_params = http_build_query($request_params); 
                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                $keyboard=array(
                    'one_time'=>false,
                    'buttons'=>array(
                        array(
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>'{"command":"CheckRequests"}',
                                    'label'=>'Проверка заявок'
                                ),
                                'color'=>'primary'
                            ),
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>'{"command":"GetAncet"}',
                                    'label'=>'Получить анкету'
                                ),
                                'color'=>'primary'
                            )
                        )
                    )
                );              
                $request_params = array( 
                    'message' => "Рады видеть вас, ".$user_name."!", 
                    'peer_id' => $user_id, 
                    'access_token' => $token,
                    'keyboard' => json_encode($keyboard),
                    'random_id'=>$random_id,
                    'v' => '5.80'
                ); 
                $get_params = http_build_query($request_params); 
                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                exit();
            break;
            case 'GetAncet':
                $keyboard=array(
                    'one_time'=>false,
                    'buttons'=>array(
                        array(
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>'{"command":"toStart"}',
                                    'label'=>'В начало'
                                ),
                                'color'=>'primary'
                            )
                        )
                    )
                );
                $sql="UPDATE `users` SET `stage`='6' WHERE `id`='$user_id'";
                mysqli_query($dbc, $sql);
                $request_params = array( 
                    'message' => "Пришли мне id пользователя(из вк)", 
                    'peer_id' => $user_id, 
                    'access_token' => $token,
                    'keyboard' => json_encode($keyboard),
                    'random_id'=>$random_id,
                    'v' => '5.80'
                ); 
                $get_params = http_build_query($request_params); 
                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                exit();
            break;
            case 'toStart':
                $sql="UPDATE `users` SET `stage`='5' WHERE `id`='$user_id'";
                mysqli_query($dbc, $sql);
                $keyboard=array(
                    'one_time'=>false,
                    'buttons'=>array(
                        array(
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>'{"command":"CheckRequests"}',
                                    'label'=>'Проверка заявок'
                                ),
                                'color'=>'primary'
                            ),
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>'{"command":"GetAncet"}',
                                    'label'=>'Получить анкету'
                                ),
                                'color'=>'primary'
                            )
                        )
                    )
                );              
                $request_params = array( 
                    'message' => "Рады видеть вас, ".$user_name."!", 
                    'peer_id' => $user_id, 
                    'access_token' => $token,
                    'keyboard' => json_encode($keyboard),
                    'random_id'=>$random_id,
                    'v' => '5.80'
                ); 
                $get_params = http_build_query($request_params); 
                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                exit();
            break;
        }
        switch ($row['stage']){
            case 6:
                $ancet_user_id=$msg;
                $sql="SELECT * FROM `promos` WHERE `id`='$ancet_user_id'";
                $result=mysqli_query($dbc, $sql);
                if (mysqli_num_rows($result)==0){
                    $sql="UPDATE `users` SET `stage`='5' WHERE `id`='$user_id'";
                    mysqli_query($dbc, $sql);
                    $keyboard=array(
                        'one_time'=>false,
                        'buttons'=>array(
                            array(
                                array(
                                    'action'=>array(
                                        'type'=>'text',
                                        'payload'=>'{"command":"CheckRequests"}',
                                        'label'=>'Проверка заявок'
                                    ),
                                    'color'=>'primary'
                                ),
                                array(
                                    'action'=>array(
                                        'type'=>'text',
                                        'payload'=>'{"command":"GetAncet"}',
                                        'label'=>'Получить анкету'
                                    ),
                                    'color'=>'primary'
                                )
                            )
                        )
                    );              
                    $request_params = array( 
                        'message' => "Такой анкеты не существует!", 
                        'peer_id' => $user_id, 
                        'access_token' => $token,
                        'keyboard' => json_encode($keyboard),
                        'random_id'=>$random_id,
                        'v' => '5.80'
                    ); 
                    $get_params = http_build_query($request_params); 
                    file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                    exit();
                }else{
                    $row=mysqli_fetch_array($result, MYSQLI_ASSOC);
                    $sql="UPDATE `users` SET `stage`='5' WHERE `id`='$user_id'";
                    mysqli_query($dbc, $sql);
                    $vk_id=$row['id'];
                    $full_name=$row['full_name'];
                    $gender=$row['gender'];
                    $summary=$row['summary'];
                    $url=$row['photo'];
                    if ($gender==1){
                        $gender='парень';
                    }else{
                        $gender='девушка';
                    }
                    $request_params=array(
                        'peer_id'=>$user_id,
                        'access_token'=>$token,
                        'v'=>'5.80'
                    );
                    $get_params=http_build_query($request_params);
                    $messages_upload_server=json_decode(file_get_contents('https://api.vk.com/method/photos.getMessagesUploadServer?'. $get_params));
                    $messages_upload_server=$messages_upload_server->response->upload_url;

                    $curl = curl_init($messages_upload_server);
                    curl_setopt($curl, CURLOPT_POST, true);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, array('file' => new CURLfile($url)));
                    $json = curl_exec($curl);
                    curl_close($curl);

                    $upload_result=json_decode($json);

                    $photos=$upload_result->photo;
                    $servers=$upload_result->server;
                    $hash=$upload_result->hash;
                
                    $request_params=array(
                        'photo' => $photos,
                        'server' => $servers,
                        'hash' => $hash,
                        'access_token' => $token,
                        'v'=>'5.80'
                    );
                    $get_params=http_build_query($request_params);
                    $photo=json_decode(file_get_contents('https://api.vk.com/method/photos.saveMessagesPhoto?'. $get_params));                    

                    $photo_id=$photo->response[0]->id;
                    $owner_id=$photo->response[0]->owner_id;
                    $photo="photo".$owner_id."_".$photo_id;
                    $keyboard=array(
                        'one_time'=>false,
                        'buttons'=>array(
                            array(
                                array(
                                    'action'=>array(
                                        'type'=>'text',
                                        'payload'=>"{\"command\":\"SetRoleLevel\", \"id\":\"$vk_id\"}",
                                        'label'=>'Установить уровень прав'
                                    ),
                                    'color'=>'primary'
                                )
                            ),
                            array(
                                array(
                                    'action'=>array(
                                        'type'=>'text',
                                        'payload'=>"{\"command\":\"DecineRequest\", \"id\":\"$vk_id\"}",
                                        'label'=>'Удалить анкету'
                                    ),
                                    'color'=>'negative'
                                ),
                                array(
                                    'action'=>array(
                                        'type'=>'text',
                                        'payload'=>"{\"command\":\"toStart\"}",
                                        'label'=>'В начало'
                                    ),
                                    'color'=>'positive'
                                )
                            )
                        )
                    );               
                    $request_params = array( 
                        'message' => "Имя: $full_name,
                        Пол: $gender,
                        Резюме: \n $summary", 
                        'peer_id' => $user_id, 
                        'access_token' => $token,
                        'attachment'=>$photo,
                        'keyboard' => json_encode($keyboard),
                        'random_id'=>$random_id,
                        'v' => '5.80'
                    ); 
                    $get_params = http_build_query($request_params); 
                    file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                    exit();
                }
            break;
            default:
            $keyboard=array(
                'one_time'=>false,
                'buttons'=>array(
                    array(
                        array(
                            'action'=>array(
                                'type'=>'text',
                                'payload'=>'{"command":"CheckRequests"}',
                                'label'=>'Проверка заявок'
                            ),
                            'color'=>'primary'
                        ),
                        array(
                            'action'=>array(
                                'type'=>'text',
                                'payload'=>'{"command":"GetAncet"}',
                                'label'=>'Получить анкету'
                            ),
                            'color'=>'primary'
                        )
                    )
                )
            );              
            $request_params = array( 
                'message' => "Рады видеть вас, ".$user_name."!", 
                'peer_id' => $user_id, 
                'access_token' => $token,
                'keyboard' => json_encode($keyboard),
                'random_id'=>$random_id,
                'v' => '5.80'
            ); 
            $get_params = http_build_query($request_params); 
            file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
            exit();
            break;
        }
    }
    $sql="SELECT `stage` FROM `users` WHERE `id`=$user_id AND `roles`=2";
    $result=mysqli_query($dbc, $sql);
    $row=mysqli_fetch_array($result);
    if (mysqli_num_rows($result)!=0){
        switch ($row['stage']){
            case 14:
                $sql="UPDATE `carts` SET `time`='$msg', `stage`='3' WHERE `owner_id`='$user_id' AND `stage`='2'";
                mysqli_query($dbc, $sql);
                $sql="UPDATE `users` SET `stage`='5' WHERE `id`='$user_id'";
                mysqli_query($dbc, $sql);
                $keyboard=array(
                    'one_time'=>true,
                    'buttons'=>array(
                        array(
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>'{"command":"toStart"}',
                                    'label'=>'В начало'
                                ),
                                'color'=>'primary'
                            )
                        )
                    )
                );              
                $request_params = array( 
                    'message' => "Заказ успешно зарегистрирован!", 
                    'peer_id' => $user_id, 
                    'access_token' => $token,
                    'keyboard' => json_encode($keyboard),
                    'random_id'=>$random_id,
                    'v' => '5.80'
                ); 
                $get_params = http_build_query($request_params); 
                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                exit();
            break;
            case 13:
                $sql="UPDATE `carts` SET `date`='$msg' WHERE `owner_id`='$user_id' AND `stage`='2'";
                mysqli_query($dbc, $sql);
                $sql="UPDATE `users` SET `stage`='14' WHERE `id`='$user_id'";
                mysqli_query($dbc, $sql);
                $request_params = array( 
                    'message' => "Укажите время работы в формате ЧЧ:ММ-ЧЧ:ММ", 
                    'peer_id' => $user_id, 
                    'access_token' => $token,
                    'random_id'=>$random_id,
                    'v' => '5.80'
                ); 
                $get_params = http_build_query($request_params); 
                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                exit();
            break;
            case 10: 
                $latitude=$data->object->geo->coordinates->latitude;
                //широта
                $longitude=$data->object->geo->coordinates->longitude;
                //долгота
                if(!isset($latitude) or !isset($longitude)){
                    $request_params = array( 
                        'message' => "Для указания точки прикрепите местоположение к сообщению ВК", 
                        'peer_id' => $user_id, 
                        'access_token' => $token,
                        'random_id'=>$random_id,
                        'v' => '5.80'
                    ); 
                    $get_params = http_build_query($request_params); 
                    file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                    exit();
                }else{
                    $sql="UPDATE `carts` SET `latitude`='$latitude', `longitude`='$longitude', `stage`='2' WHERE `owner_id`='$user_id' AND `stage`='1'";
                    mysqli_query($dbc, $sql);
                    $sql="UPDATE `users` SET `stage`='11' WHERE `id`='$user_id'";
                    mysqli_query($dbc, $sql);
                    $keyboard=array(
                        'one_time'=>true,
                        'buttons'=>array(
                            array(
                                array(
                                    'action'=>array(
                                        'type'=>'text',
                                        'payload'=>'{"command":"needOnePromo"}',
                                        'label'=>'1'
                                    ),
                                    'color'=>'positive'
                                ),
                                array(
                                    'action'=>array(
                                        'type'=>'text',
                                        'payload'=>'{"command":"needTwoPromo"}',
                                        'label'=>'2'
                                    ),
                                    'color'=>'positive'
                                ),
                                array(
                                    'action'=>array(
                                        'type'=>'text',
                                        'payload'=>'{"command":"needThreePromo"}',
                                        'label'=>'3'
                                    ),
                                    'color'=>'positive'
                                )
                            ),
                            array(
                                array(
                                    'action'=>array(
                                        'type'=>'text',
                                        'payload'=>'{"command":"needFourPromo"}',
                                        'label'=>'4'
                                    ),
                                    'color'=>'positive'
                                ),
                                array(
                                    'action'=>array(
                                        'type'=>'text',
                                        'payload'=>'{"command":"needFivePromo"}',
                                        'label'=>'5'
                                    ),
                                    'color'=>'positive'
                                ),
                                array(
                                    'action'=>array(
                                        'type'=>'text',
                                        'payload'=>'{"command":"toStart"}',
                                        'label'=>'В начало'
                                    ),
                                    'color'=>'negative'
                                )
                            )
                        )
                    );
                    $request_params = array( 
                        'message' => "Сколько должно быть промоутеров?", 
                        'peer_id' => $user_id, 
                        'access_token' => $token,
                        'keyboard'=>json_encode($keyboard),
                        'random_id'=>$random_id,
                        'v' => '5.80'
                    ); 
                    $get_params = http_build_query($request_params); 
                    file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                    exit();
                }
            break;
        }
        switch($action){
            case 'decine':
                $sql="SELECT `promos` FROM `carts` WHERE `owner_id`='$user_id' AND `stage`='2'";
                $result=mysqli_query($dbc, $sql);
                $row=mysqli_fetch_array($result, MYSQLI_ASSOC);
                $promos=json_decode($row['promos']);
                $count_promos=$promos->count_promo;
                $promo=$promos->promos;
                $need_promos=$count_promos-count($promo);
                $sql="UPDATE `users` SET `stage`='12' WHERE `id`='$user_id'";
                mysqli_query($dbc, $sql);
                $sql="SELECT * FROM `promos` WHERE `confirmed`='1' ORDER BY rand() LIMIT 1";
                $result=mysqli_query($dbc, $sql);
                $row=mysqli_fetch_array($result, MYSQLI_ASSOC);
                    $vk_id=$row['id'];
                    $full_name=$row['full_name'];
                    $gender=$row['gender'];
                    $summary=$row['summary'];
                    $url=$row['photo'];
                    if ($gender==1){
                        $gender='парень';
                    }else{
                        $gender='девушка';
                    }
                    $request_params=array(
                        'peer_id'=>$user_id,
                        'access_token'=>$token,
                        'v'=>'5.80'
                    );
                    $get_params=http_build_query($request_params);
                    $messages_upload_server=json_decode(file_get_contents('https://api.vk.com/method/photos.getMessagesUploadServer?'. $get_params));
                    $messages_upload_server=$messages_upload_server->response->upload_url;

                    $curl = curl_init($messages_upload_server);
                    curl_setopt($curl, CURLOPT_POST, true);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, array('file' => new CURLfile($url)));
                    $json = curl_exec($curl);
                    curl_close($curl);

                    $upload_result=json_decode($json);

                    $photos=$upload_result->photo;
                    $servers=$upload_result->server;
                    $hash=$upload_result->hash;
                
                    $request_params=array(
                        'photo' => $photos,
                        'server' => $servers,
                        'hash' => $hash,
                        'access_token' => $token,
                        'v'=>'5.80'
                    );
                    $get_params=http_build_query($request_params);
                    $photo=json_decode(file_get_contents('https://api.vk.com/method/photos.saveMessagesPhoto?'. $get_params));                    

                    $photo_id=$photo->response[0]->id;
                    $owner_id=$photo->response[0]->owner_id;
                    $photo="photo".$owner_id."_".$photo_id;
                $keyboard=array(
                    'one_time'=>true,
                    'buttons'=>array(
                        array(
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>"{\"command\":\"accept\", \"id\":\"$vk_id\"}",
                                    'label'=>'Подходит!'
                                ),
                                'color'=>'positive'
                            ),
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>'{"command":"decine"}',
                                    'label'=>'Не подходит'
                                ),
                                'color'=>'negative'
                            )
                        )
                    )
                );              
                $request_params = array( 
                    'message' => "Предлагаем этого промоутера:
                    Имя: $full_name,
                    Пол: $gender,
                    Дополнительная информация: \n $summary", 
                    'peer_id' => $user_id, 
                    'access_token' => $token,
                    'keyboard' => json_encode($keyboard),
                    'random_id'=>$random_id,
                    'attachment'=>$photo,
                    'v' => '5.80'
                ); 
                $get_params = http_build_query($request_params); 
                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                exit();
            break;
            case 'accept':
                $sql="SELECT `promos` FROM `carts` WHERE `owner_id`='$user_id' AND `stage`='2'";
                $result=mysqli_query($dbc, $sql);
                $row=mysqli_fetch_array($result, MYSQLI_ASSOC);
                $promos=json_decode($row['promos']);
                $thisPromoId=$btn_payload->id;
                $count_promos=$promos->count_promo;
                $promo=$promos->promos;
                array_push($promo, $thisPromoId);
                $array=array(
                    'count_promo'=>$count_promos,
                    'promos'=>$promo
                );
                $json=json_encode($array);
                $sql="UPDATE `carts` SET `promos`='$json' WHERE `owner_id`='$user_id' AND `stage`='2'";
                mysqli_query($dbc, $sql);
                $need_promos=$count_promos-count($promo);
                if ($need_promos==0){
                    $sql="UPDATE `users` SET `stage`='13' WHERE `id`='$user_id'";
                    mysqli_query($dbc, $sql);
                    $request_params = array( 
                        'message' => "Укажите дату в формате дд.мм.гггг", 
                        'peer_id' => $user_id, 
                        'access_token' => $token,
                        'random_id'=>$random_id,
                        'v' => '5.80'
                    ); 
                    $get_params = http_build_query($request_params); 
                    file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                }else{
                    $sql="SELECT * FROM `promos` WHERE `confirmed`='1' ORDER BY rand() LIMIT 1";
                    $result=mysqli_query($dbc, $sql);
                    $row=mysqli_fetch_array($result, MYSQLI_ASSOC);
                        $vk_id=$row['id'];
                        $full_name=$row['full_name'];
                        $gender=$row['gender'];
                        $summary=$row['summary'];
                        $url=$row['photo'];
                        if ($gender==1){
                            $gender='парень';
                        }else{
                            $gender='девушка';
                        }
                        $request_params=array(
                            'peer_id'=>$user_id,
                            'access_token'=>$token,
                            'v'=>'5.80'
                        );
                        $get_params=http_build_query($request_params);
                        $messages_upload_server=json_decode(file_get_contents('https://api.vk.com/method/photos.getMessagesUploadServer?'. $get_params));
                        $messages_upload_server=$messages_upload_server->response->upload_url;

                        $curl = curl_init($messages_upload_server);
                        curl_setopt($curl, CURLOPT_POST, true);
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($curl, CURLOPT_POSTFIELDS, array('file' => new CURLfile($url)));
                        $json = curl_exec($curl);
                        curl_close($curl);

                        $upload_result=json_decode($json);

                        $photos=$upload_result->photo;
                        $servers=$upload_result->server;
                        $hash=$upload_result->hash;
                    
                        $request_params=array(
                            'photo' => $photos,
                            'server' => $servers,
                            'hash' => $hash,
                            'access_token' => $token,
                            'v'=>'5.80'
                        );
                        $get_params=http_build_query($request_params);
                        $photo=json_decode(file_get_contents('https://api.vk.com/method/photos.saveMessagesPhoto?'. $get_params));                    

                        $photo_id=$photo->response[0]->id;
                        $owner_id=$photo->response[0]->owner_id;
                        $photo="photo".$owner_id."_".$photo_id;
                    $keyboard=array(
                        'one_time'=>true,
                        'buttons'=>array(
                            array(
                                array(
                                    'action'=>array(
                                        'type'=>'text',
                                        'payload'=>"{\"command\":\"accept\", \"id\":\"$vk_id\"}",
                                        'label'=>'Подходит!'
                                    ),
                                    'color'=>'positive'
                                ),
                                array(
                                    'action'=>array(
                                        'type'=>'text',
                                        'payload'=>'{"command":"decine"}',
                                        'label'=>'Не подходит'
                                    ),
                                    'color'=>'negative'
                                )
                            )
                        )
                    );              
                    $request_params = array( 
                        'message' => "Предлагаем этого промоутера:
                        Имя: $full_name,
                        Пол: $gender,
                        Дополнительная информация: \n $summary", 
                        'peer_id' => $user_id, 
                        'access_token' => $token,
                        'keyboard' => json_encode($keyboard),
                        'random_id'=>$random_id,
                        'attachment'=>$photo,
                        'v' => '5.80'
                    ); 
                    $get_params = http_build_query($request_params); 
                    file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                }
                exit();
            break;
            case 'iWantPick':
                $sql="SELECT `promos` FROM `carts` WHERE `owner_id`='$user_id' AND `stage`='2'";
                $result=mysqli_query($dbc, $sql);
                $row=mysqli_fetch_array($result, MYSQLI_ASSOC);
                $promos=json_decode($row['promos']);
                $count_promos=$promos->count_promo;
                $promo=$promos->promos;
                $need_promos=$count_promos-count($promo);
                $sql="UPDATE `users` SET `stage`='12' WHERE `id`='$user_id'";
                mysqli_query($dbc, $sql);
                $sql="SELECT * FROM `promos` WHERE `confirmed`='1' ORDER BY rand() LIMIT 1";
                $result=mysqli_query($dbc, $sql);
                $row=mysqli_fetch_array($result, MYSQLI_ASSOC);
                    $vk_id=$row['id'];
                    $full_name=$row['full_name'];
                    $gender=$row['gender'];
                    $summary=$row['summary'];
                    $url=$row['photo'];
                    if ($gender==1){
                        $gender='парень';
                    }else{
                        $gender='девушка';
                    }
                    $request_params=array(
                        'peer_id'=>$user_id,
                        'access_token'=>$token,
                        'v'=>'5.80'
                    );
                    $get_params=http_build_query($request_params);
                    $messages_upload_server=json_decode(file_get_contents('https://api.vk.com/method/photos.getMessagesUploadServer?'. $get_params));
                    $messages_upload_server=$messages_upload_server->response->upload_url;

                    $curl = curl_init($messages_upload_server);
                    curl_setopt($curl, CURLOPT_POST, true);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, array('file' => new CURLfile($url)));
                    $json = curl_exec($curl);
                    curl_close($curl);

                    $upload_result=json_decode($json);

                    $photos=$upload_result->photo;
                    $servers=$upload_result->server;
                    $hash=$upload_result->hash;
                
                    $request_params=array(
                        'photo' => $photos,
                        'server' => $servers,
                        'hash' => $hash,
                        'access_token' => $token,
                        'v'=>'5.80'
                    );
                    $get_params=http_build_query($request_params);
                    $photo=json_decode(file_get_contents('https://api.vk.com/method/photos.saveMessagesPhoto?'. $get_params));                    

                    $photo_id=$photo->response[0]->id;
                    $owner_id=$photo->response[0]->owner_id;
                    $photo="photo".$owner_id."_".$photo_id;
                $keyboard=array(
                    'one_time'=>true,
                    'buttons'=>array(
                        array(
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>"{\"command\":\"accept\", \"id\":\"$vk_id\"}",
                                    'label'=>'Подходит!'
                                ),
                                'color'=>'positive'
                            ),
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>'{"command":"decine"}',
                                    'label'=>'Не подходит'
                                ),
                                'color'=>'negative'
                            )
                        )
                    )
                );              
                $request_params = array( 
                    'message' => "Предлагаем этого промоутера:
                    Имя: $full_name,
                    Пол: $gender,
                    Дополнительная информация: \n $summary", 
                    'peer_id' => $user_id, 
                    'access_token' => $token,
                    'keyboard' => json_encode($keyboard),
                    'random_id'=>$random_id,
                    'attachment'=>$photo,
                    'v' => '5.80'
                ); 
                $get_params = http_build_query($request_params); 
                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                exit();
            break;
            case 'youWillPick':
                $sql="UPDATE `users` SET `stage`='13' WHERE `id`='$user_id'";
                mysqli_query($dbc, $sql);
                $request_params = array( 
                    'message' => "Укажите дату в формате дд.мм.гггг", 
                    'peer_id' => $user_id, 
                    'access_token' => $token,
                    'random_id'=>$random_id,
                    'v' => '5.80'
                ); 
                $get_params = http_build_query($request_params); 
                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
            break;
            case 'needOnePromo':
                $array=array(
                    'count_promo'=>1,
                    'promos'=>array()
                );
                $json=json_encode($array);
                $sql="UPDATE `carts` SET `promos`='$json' WHERE `owner_id`='$user_id' AND `stage`='2'";
                mysqli_query($dbc, $sql);
                $keyboard=array(
                    'one_time'=>true,
                    'buttons'=>array(
                        array(
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>'{"command":"iWantPick"}',
                                    'label'=>'Я выберу'
                                ),
                                'color'=>'primary'
                            ),
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>'{"command":"youWillPick"}',
                                    'label'=>'Вам выбирать'
                                ),
                                'color'=>'primary'
                            )
                        )
                    )
                );              
                $request_params = array( 
                    'message' => "Можете выбрать желаемых промоутеров, либо оставить выбор нам.", 
                    'peer_id' => $user_id, 
                    'access_token' => $token,
                    'keyboard' => json_encode($keyboard),
                    'random_id'=>$random_id,
                    'v' => '5.80'
                ); 
                $get_params = http_build_query($request_params); 
                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                exit();
            break;
            case 'needTwoPromo':
                $array=array(
                    'count_promo'=>2,
                    'promos'=>array()
                );
                $json=json_encode($array);
                $sql="UPDATE `carts` SET `promos`='$json' WHERE `owner_id`='$user_id' AND `stage`='2'";
                mysqli_query($dbc, $sql);
                $keyboard=array(
                    'one_time'=>true,
                    'buttons'=>array(
                        array(
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>'{"command":"iWantPick"}',
                                    'label'=>'Я выберу'
                                ),
                                'color'=>'primary'
                            ),
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>'{"command":"youWillPick"}',
                                    'label'=>'Вам выбирать'
                                ),
                                'color'=>'primary'
                            )
                        )
                    )
                );              
                $request_params = array( 
                    'message' => "Можете выбрать желаемых промоутеров, либо оставить выбор нам.", 
                    'peer_id' => $user_id, 
                    'access_token' => $token,
                    'keyboard' => json_encode($keyboard),
                    'random_id'=>$random_id,
                    'v' => '5.80'
                ); 
                $get_params = http_build_query($request_params); 
                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                exit();
            break;
            case 'needThreePromo':
                $array=array(
                    'count_promo'=>3,
                    'promos'=>array()
                );
                $json=json_encode($array);
                $sql="UPDATE `carts` SET `promos`='$json' WHERE `owner_id`='$user_id' AND `stage`='2'";
                mysqli_query($dbc, $sql);
                $keyboard=array(
                    'one_time'=>true,
                    'buttons'=>array(
                        array(
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>'{"command":"iWantPick"}',
                                    'label'=>'Я выберу'
                                ),
                                'color'=>'primary'
                            ),
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>'{"command":"youWillPick"}',
                                    'label'=>'Вам выбирать'
                                ),
                                'color'=>'primary'
                            )
                        )
                    )
                );              
                $request_params = array( 
                    'message' => "Можете выбрать желаемых промоутеров, либо оставить выбор нам.", 
                    'peer_id' => $user_id, 
                    'access_token' => $token,
                    'keyboard' => json_encode($keyboard),
                    'random_id'=>$random_id,
                    'v' => '5.80'
                ); 
                $get_params = http_build_query($request_params); 
                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                exit();
            break;
            case 'needFourPromo':
                $array=array(
                    'count_promo'=>4,
                    'promos'=>array()
                );
                $json=json_encode($array);
                $sql="UPDATE `carts` SET `promos`='$json' WHERE `owner_id`='$user_id' AND `stage`='2'";
                mysqli_query($dbc, $sql);
                $keyboard=array(
                    'one_time'=>true,
                    'buttons'=>array(
                        array(
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>'{"command":"iWantPick"}',
                                    'label'=>'Я выберу'
                                ),
                                'color'=>'primary'
                            ),
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>'{"command":"youWillPick"}',
                                    'label'=>'Вам выбирать'
                                ),
                                'color'=>'primary'
                            )
                        )
                    )
                );              
                $request_params = array( 
                    'message' => "Можете выбрать желаемых промоутеров, либо оставить выбор нам.", 
                    'peer_id' => $user_id, 
                    'access_token' => $token,
                    'keyboard' => json_encode($keyboard),
                    'random_id'=>$random_id,
                    'v' => '5.80'
                ); 
                $get_params = http_build_query($request_params); 
                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                exit();
            break;
            case 'needFivePromo':
                $array=array(
                    'count_promo'=>5,
                    'promos'=>array()
                );
                $json=json_encode($array);
                $sql="UPDATE `carts` SET `promos`='$json' WHERE `owner_id`='$user_id' AND `stage`='2'";
                mysqli_query($dbc, $sql);
                $keyboard=array(
                    'one_time'=>true,
                    'buttons'=>array(
                        array(
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>'{"command":"iWantPick"}',
                                    'label'=>'Я выберу'
                                ),
                                'color'=>'primary'
                            ),
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>'{"command":"youWillPick"}',
                                    'label'=>'Вам выбирать'
                                ),
                                'color'=>'primary'
                            )
                        )
                    )
                );              
                $request_params = array( 
                    'message' => "Можете выбрать желаемых промоутеров, либо оставить выбор нам.", 
                    'peer_id' => $user_id, 
                    'access_token' => $token,
                    'keyboard' => json_encode($keyboard),
                    'random_id'=>$random_id,
                    'v' => '5.80'
                ); 
                $get_params = http_build_query($request_params); 
                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                exit();
            break;
            case 'createCart':
                $sql="SELECT * FROM `carts`";
                $result=mysqli_query($dbc, $sql);
                $id=mysqli_num_rows($result);
                $sql="INSERT INTO `carts`(`id`, `owner_id`, `latitude`, `longitude`, `promos`, `date`, `time`, `stage`) VALUES ('$id', '$user_id', NULL, NULL, NULL, NULL, NULL, '1')";
                mysqli_query($dbc, $sql);
                $sql="UPDATE `users` SET `stage`='10' WHERE `id`='$user_id'";
                mysqli_query($dbc, $sql);
                $keyboard=array(
                    'one_time'=>false,
                    'buttons'=>array(
                        array(
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>'{"command":"toStart"}',
                                    'label'=>'В начало'
                                ),
                                'color'=>'primary'
                            )
                        )
                    )
                );    
                $request_params = array( 
                    'message' => "Укажите точку работы(пришлите местоположение ВК)", 
                    'peer_id' => $user_id, 
                    'access_token' => $token,
                    'random_id'=>$random_id,
                    'v' => '5.80'
                ); 
                $get_params = http_build_query($request_params); 
                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                exit();
            break;
            case 'toStart':
                $keyboard=array(
                    'one_time'=>true,
                    'buttons'=>array(
                        array(
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>'{"command":"createCart"}',
                                    'label'=>'Сделать заказ'
                                ),
                                'color'=>'positive'
                            ),
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>"{\"command\":\"myCarts\", \"id\":\"$user_id\"}",
                                    'label'=>'Мои заказы'
                                ),
                                'color'=>'primary'
                            )
                        )
                    )
                );              
                $request_params = array( 
                    'message' => "Рады видеть вас, ".$user_name."!", 
                    'peer_id' => $user_id, 
                    'access_token' => $token,
                    'keyboard' => json_encode($keyboard),
                    'random_id'=>$random_id,
                    'v' => '5.80'
                ); 
                $get_params = http_build_query($request_params); 
                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                exit();
            break;
        }
    }

    switch($action){
        case 'start':
            $keyboard=array(
                'one_time'=>true,
                'buttons'=>array(
                    array(
                        array(
                            'action'=>array(
                                'type'=>'text',
                                'payload'=>'{"command":"IAmPromoter"}',
                                'label'=>'Я промоутер'
                            ),
                            'color'=>'primary'
                        ),
                        array(
                            'action'=>array(
                                'type'=>'text',
                                'payload'=>'{"command":"IAmClient"}',
                                'label'=>'Я заказчик'
                            ),
                            'color'=>'primary'
                        )
                    )
                )
            );              
            $request_params = array( 
                'message' => "Ты у нас кто?", 
                'peer_id' => $user_id, 
                'access_token' => $token,
                'keyboard' => json_encode($keyboard),
                'random_id'=>$random_id,
                'v' => '5.80'
            ); 
            $get_params = http_build_query($request_params); 
            file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
            exit();
        break;
        case 'IAmClient':
            $reg_date=$data->object->date;
            $sql="INSERT INTO `clients`(`id`, `reg_date`, `confirmed`) VALUES ('$user_id', '$reg_date', '0')";
            mysqli_query($dbc, $sql);
            $sql="INSERT INTO `users`(`id`, `roles`, `stage`) VALUES ('$user_id', '2', '1')";
            mysqli_query($dbc, $sql);
            $keyboard=array(
                'one_time'=>true,
                'buttons'=>array(
                    array(
                        array(
                            'action'=>array(
                                'type'=>'text',
                                'payload'=>'{"command":"toStart"}',
                                'label'=>'В начало'
                            ),
                            'color'=>'primary'
                        )
                    )
                )
            );      
            $request_params = array( 
                'message' => "Вы зарегестрированы!", 
                'peer_id' => $user_id, 
                'access_token' => $token,
                'keyboard' => json_encode($keyboard),
                'random_id'=>$random_id,
                'v' => '5.80'
            );
            $get_params = http_build_query($request_params); 
            file_get_contents('https://api.vk.com/method/messages.send?'. $get_params); 
            exit();
        break;
        case 'IAmPromoter':
            $sql="INSERT INTO `promos`(`id`, `confirmed`) VALUES ('$user_id', '0')";
            mysqli_query($dbc, $sql);
            $sql="INSERT INTO `users`(`id`, `roles`, `stage`) VALUES ('$user_id', '1', '1')";
            mysqli_query($dbc, $sql);
            $keyboard=array(
                'one_time'=>true,
                'buttons'=>array(
                    array(
                        array(
                            'action'=>array(
                                'type'=>'text',
                                'payload'=>'{"command":"IAmGirl"}',
                                'label'=>'Я девушка'
                            ),
                            'color'=>'primary'
                        ),
                        array(
                            'action'=>array(
                                'type'=>'text',
                                'payload'=>'{"command":"IAmBoy"}',
                                'label'=>'Я парень'
                            ),
                            'color'=>'primary'
                        )
                    )
                )
            );      
            $request_params = array( 
                'message' => "Какого ты пола?", 
                'peer_id' => $user_id, 
                'access_token' => $token,
                'keyboard' => json_encode($keyboard),
                'random_id'=>$random_id,
                'v' => '5.80'
            );
            $get_params = http_build_query($request_params); 
            file_get_contents('https://api.vk.com/method/messages.send?'. $get_params); 
            exit();
        break;
        case 'IAmGirl':
            $sql="UPDATE `promos` SET `gender`='0' WHERE `id`='$user_id'";
            mysqli_query($dbc, $sql);
            $sql="UPDATE `users` SET `stage`='2' WHERE `id`='$user_id'";
            mysqli_query($dbc, $sql);
            $request_params = array( 
                'message' => "Как тебя зовут?", 
                'peer_id' => $user_id, 
                'access_token' => $token,
                'random_id'=>$random_id,
                'v' => '5.80'
            );
            $get_params = http_build_query($request_params); 
            file_get_contents('https://api.vk.com/method/messages.send?'. $get_params); 
            exit();
        break;
        case 'IAmBoy':
            $sql="UPDATE `promos` SET `gender`='1' WHERE `id`='$user_id'";
            mysqli_query($dbc, $sql);
            $sql="UPDATE `users` SET `stage`='2' WHERE `id`='$user_id'";
            mysqli_query($dbc, $sql);
            $request_params = array( 
                'message' => "Как тебя зовут(ФИО)?", 
                'peer_id' => $user_id, 
                'access_token' => $token,
                'random_id'=>$random_id,
                'v' => '5.80'
            );
            $get_params = http_build_query($request_params); 
            file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
            exit(); 
        break;
        default:
            $sql="SELECT * FROM `users` WHERE `id`='$user_id'";
            $result=mysqli_query($dbc, $sql);
            if (mysqli_num_rows($result)==0){
                $keyboard=array(
                    'one_time'=>true,
                    'buttons'=>array(
                        array(
                            array(
                                'action'=>array(
                                    'type'=>'text',
                                    'payload'=>'{"command":"start"}',
                                    'label'=>'Начать'
                                ),
                                'color'=>'primary'
                            )
                        )
                    )
                );  
                $request_params = array( 
                    'message' => "Вы не зерегистрированы.", 
                    'peer_id' => $user_id, 
                    'access_token' => $token,
                    'keyboard' => json_encode($keyboard),
                    'random_id'=>$random_id,
                    'v' => '5.80'
                );
                $get_params = http_build_query($request_params); 
                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                exit();    
            }else{
                $row=mysqli_fetch_array($result, MYSQLI_ASSOC);
                $stage=$row['stage'];
                $role=$row['roles'];
                if ($stage==2 and $role==1){
                    $sql="UPDATE `promos` SET `full_name`='$msg' WHERE `id`='$user_id'";
                    mysqli_query($dbc, $sql);
                    $sql="UPDATE `users` SET `stage`='3' WHERE `id`='$user_id'";
                    mysqli_query($dbc, $sql);
                    $request_params = array( 
                        'message' => "Оставь свои контакты. Опиши свои главные качества. Почему хочешь работать с нами? Как долго планируешь сотрудничать? Как часто готов работать? Какая станция метро ближайшая?", 
                        'peer_id' => $user_id, 
                        'access_token' => $token,
                        'random_id'=>$random_id,
                        'v' => '5.80'
                    );
                    $get_params = http_build_query($request_params); 
                    file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                    exit();  
                }
                if ($stage==3 and $role==1){
                    $sql="UPDATE `promos` SET `summary`='$msg' WHERE `id`='$user_id'";
                    mysqli_query($dbc, $sql);
                    $sql="UPDATE `users` SET `stage`='4' WHERE `id`='$user_id'";
                    mysqli_query($dbc, $sql);
                    $request_params = array( 
                        'message' => "Пришли мне своё фото.", 
                        'peer_id' => $user_id, 
                        'access_token' => $token,
                        'random_id'=>$random_id,
                        'v' => '5.80'
                    );
                    $get_params = http_build_query($request_params); 
                    file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                    exit();  
                }
                if ($stage==4 and $role==1){
                    $attach=$data->object->attachments[0];
                    if (!isset($attach)){
                        $request_params = array( 
                            'message' => "Пришли мне своё фото.", 
                            'peer_id' => $user_id, 
                            'access_token' => $token,
                            'random_id'=>$random_id,
                            'v' => '5.80'
                        );
                        $get_params = http_build_query($request_params); 
                        file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                        exit();  
                    }else{
                        if ($attach->type!="photo"){
                            $request_params = array( 
                                'message' => "Мне нужно фото!", 
                                'peer_id' => $user_id, 
                                'access_token' => $token,
                                'random_id'=>$random_id,
                                'v' => '5.80'
                            );
                            $get_params = http_build_query($request_params); 
                            file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                            exit();   
                        }else{
                            $photo_url=$data->object->attachments[0]->photo->sizes[3]->url;
                            if (!isset($photo_url)){
                                $photo_url=$data->object->attachments[0]->photo->sizes[4]->url;
                            }
                            if (!isset($photo_url)){
                                $photo_url=$data->object->attachments[0]->photo->sizes[8]->url;
                            }
                            $img=imagecreatefromjpeg ($photo_url);
                            imagejpeg($img, 'photos/'.$user_id.'.jpg');
                            $sql="UPDATE `promos` SET `photo`='photos/".$user_id.".jpg' WHERE `id`='$user_id'";
                            mysqli_query($dbc, $sql);
                            $sql="UPDATE `users` SET `stage`='5' WHERE `id`='$user_id'";
                            mysqli_query($dbc, $sql);
                            $request_params = array( 
                                'message' => "Ваша заявка была отправлена. Мы свяжемся с вами позже!", 
                                'peer_id' => $user_id, 
                                'access_token' => $token,
                                'random_id'=>$random_id,
                                'v' => '5.80'
                            );
                            $get_params = http_build_query($request_params); 
                            file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                            exit();
                        }
                    }
                }
            }
        break;
    }
break; 
} 

?> 