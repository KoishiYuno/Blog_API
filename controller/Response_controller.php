<?php 

function throwErrorMessage($errorMessage, $statusCode){
    $response = new Response();
    $response->setSuccess(false);
    $response->setHttpStatusCode($statusCode);
    $response->addMessage($errorMessage);
    $response->toCache(false);
    $response->send();
}

//return data for a succuessed request
function returnData($statusCode,$data,$toCache){
    $response = new Response();
    $response->setSuccess(true);
    $response->setHttpStatusCode($statusCode);
    $response->toCache($toCache);
    $response->setData($data);
    $response->send();
}

function checkQuerySuccess($query){
    $count = $query->rowCount();

    //throw error message for no rows are found in database
    if($count === 0){
        throwErrorMessage("Content not found",404);
        exit();
    }

    return $count;
}

?>