<?php
return array(
	'APIException:ApiResultUnknown' => "API Result is of an unknown type, this should never happen.",
	'APIException:MissingParameterInMethod' => "Missing parameter %s in method %s",
	'APIException:ParameterNotArray' => "%s does not appear to be an array.",
	'APIException:UnrecognisedTypeCast' => "Unrecognised type in cast %s for variable '%s' in method '%s'",
	'APIException:TypeCastError' => "Variable '%s' in method '%s' can not be cast to type '%s'",
	'APIException:InvalidParameter' => "Invalid parameter found for '%s' in method '%s'.",
	'APIException:FunctionParseError' => "%s(%s) has a parsing error.",
	'APIException:FunctionCallError' => "Callback function '%s' for method '%s' has a call error or does not produce valid output with the following parameters: %s",
	'APIException:FunctionNoReturn' => "%s(%s) returned no value.",
	'APIException:APIAuthenticationFailed' => "Method call failed the API Authentication",
	'APIException:UserAuthenticationFailed' => "Method call failed the User Authentication",
	'APIException:MethodCallNotImplemented' => "Method call '%s' has not been implemented.",
	'APIException:FunctionDoesNotExist' => "Function for method '%s' is not callable",
	'APIException:AlgorithmNotSupported' => "Algorithm '%s' is not supported or has been disabled.",
	'APIException:NotGetOrPost' => "Request method must be GET or POST",
	'APIException:MissingAPIKey' => "Missing API key",
	'APIException:BadAPIKey' => "Bad API key",
	'APIException:MissingHmac' => "Missing X-Elgg-hmac header",
	'APIException:MissingHmacAlgo' => "Missing X-Elgg-hmac-algo header",
	'APIException:MissingTime' => "Missing X-Elgg-time header",
	'APIException:MissingNonce' => "Missing X-Elgg-nonce header",
	'APIException:TemporalDrift' => "X-Elgg-time is too far in the past or future. Epoch fail.",
	'APIException:NoQueryString' => "No data on the query string",
	'APIException:MissingPOSTHash' => "Missing X-Elgg-posthash header",
	'APIException:MissingPOSTAlgo' => "Missing X-Elgg-posthash_algo header",
	'APIException:MissingContentType' => "Missing content type for post data",
	'SecurityException:APIAccessDenied' => "Sorry, API access has been disabled by the administrator.",
	'SecurityException:NoAuthMethods' => "No authentication methods were found that could authenticate this API request.",
	'SecurityException:authenticationfailed' => "User could not be authenticated",
	'InvalidParameterException:APIMethodOrFunctionNotSet' => "Method or function not set in call in expose_method()",
	'InvalidParameterException:APIParametersArrayStructure' => "Parameters array structure is incorrect for call to expose method '%s'",
	'InvalidParameterException:UnrecognisedHttpMethod' => "Unrecognised http method %s for api method '%s'",
	'SecurityException:AuthTokenExpired' => "Authentication token either missing, invalid or expired.",
	'SecurityException:InvalidPostHash' => "POST data hash is invalid - Expected %s but got %s.",
	'SecurityException:DupePacket' => "Packet signature already seen.",
	'SecurityException:InvalidAPIKey' => "Invalid or missing API Key.",
	'NotImplementedException:CallMethodNotImplemented' => "Call method '%s' is currently not supported.",
	'CallException:InvalidCallMethod' => "%s must be called using '%s'",

	'system.api.list' => "List all available API calls on the system.",
	'auth.gettoken' => "This API call lets a user obtain a user authentication token which can be used for authenticating future API calls. Pass it as the parameter auth_token",
);
