<?php

/**
 * 文昌链接口
 * @return array
 */

class AvataLogic
{
    private $apiKey = "";
    private $apiSecret = "";
    private $domain = "";

    public function __construct($data)
    {
        $this->apiKey = $data['api_key'];
        $this->apiSecret = $data['api_secret'];
        $this->domain = $data['api_url'];
    }

    //创建链账户
    function CreateChainAccount($name, $operation_id)
    {
        $body = [
            "name" => $name,
            "operation_id" => $operation_id,
        ];
        $res = $this->request("/v1beta1/account", [], $body, "POST");
        return $res;
    }

    //查询单个创建会员状态
    function QueryChainAccount($operation_id)
    {
        $query = [
            "operation_id" => $operation_id,
        ];
        $res = $this->request("/v1beta1/accounts", $query, [], "GET");
        return $res;
    }

    //上链交易结果查询
    function OperationResult($operation_id)
    {
        $res = $this->request("/v1beta1/tx/" . $operation_id, [], [], "GET");
        return $res;
    }

    //创建NFT类别
    function CreateClasses($data, $operation_id)
    {
        $body = [
            "name" => $data['name'],
            "owner" => $data['owner'],
            "operation_id" => $operation_id,
        ];
        $res = $this->request("/v1beta1/nft/classes", [], $body, "POST");

        return $res;
    }

    //查询NFT类别详情
    function QueryClassesDetail($id)
    {
        $query = [
            "id" => $id,
        ];
        $res = $this->request("/v1beta1/nft/classes", $query, [], "GET");
        return $res;
    }

    //转让NFT类别
    function TransfersClasses($data, $operation_id)
    {
        $body = [
            "recipient" => $data['recipient'],
            "operation_id" => $operation_id,
            "tag" => !empty($data['tag']) ? $data['tag'] : '',
        ];
        $res = $this->request("/v1beta1/nft/class-transfers/" . $data['class_id'] . "/" . $data['owner'], [], $body, "POST");
        return $res;
    }

    //发行NFT
    function CreateNft($data, $operation_id)
    {
        $body = [
            "name" => $data['name'],
            "uri" => !empty($data['uri']) ? $data['uri'] : '',
            "recipient" => $data['recipient'],
            "operation_id" => $operation_id,
        ];
        if (!empty($data['uri_hash'])) {
            $body['uri_hash'] = $data['uri_hash'];
        }
        if (!empty($data['data'])) {
            $body['data'] = $data['data'];
        }
        if (!empty($data['tag'])) {
            $body['tag'] = $data['tag'];
        }
        $res = $this->request("/v1beta1/nft/nfts/" . $data['class_id'], [], $body, "POST");
        return $res;
    }

    //转让NFT
    function TransfersNft($data, $operation_id)
    {
        $body = [
            "recipient" => $data['recipient'],
            "operation_id" => $operation_id,
            "tag" => !empty($data['tag']) ? $data['tag'] : '',
        ];
        $res = $this->request("/v1beta1/nft/nft-transfers/" . $data['class_id'] . "/" . $data['owner'] . "/" . $data['nft_id'], [], $body, "POST");
        return $res;
    }

    //编辑NFT
    function EditNft($data, $operation_id)
    {
        $body = [
            "name" => $data['name'],
            "uri" => !empty($data['uri']) ? $data['uri'] : '',
            "data" => !empty($data['data']) ? $data['data'] : '',
            "operation_id" => $operation_id,
            "tag" => !empty($data['tag']) ? $data['tag'] : '',
        ];

        $res = $this->request("/v1beta1/nft/class-transfers/" . $data['class_id'] . "/" . $data['owner'] . "/" . $data['nft_id'], [], $body, "PATCH");
        return $res;
    }

    //销毁NFT
    function DeleteNft($data, $operation_id)
    {
        $body = [
            "operation_id" => $operation_id,
            "tag" => !empty($data['tag']) ? $data['tag'] : '',
        ];

        $res = $this->request("/v1beta1/nft/class-transfers/" . $data['class_id'] . "/" . $data['owner'] . "/" . $data['nft_id'], [], $body, "DELETE");
        return $res;
    }

    //查询NFT详情
    function QueryNftDetail($data)
    {
        $query = [
            "class_id" => $data['class_id'],
            "nft_id" => $data['nft_id'],
        ];
        $res = $this->request("/v1beta1/nft/nfts/" . $data['class_id'] . '/' . $data['nft_id'], $query, [], "GET");
        return $res;
    }

    function request($path, $query = [], $body = [], $method = 'GET')
    {
        $method = strtoupper($method);
        $apiGateway = rtrim($this->domain, '/') . '/' . ltrim($path,
                '/') . ($query ? '?' . http_build_query($query) : '');
        $timestamp = $this->getMillisecond();
        $params = ["path_url" => $path];
        if ($query) {
            foreach ($query as $k => $v) {
                $params["query_{$k}"] = $v;
            }
        }
        if ($body) {
            foreach ($body as $k => $v) {
                $params["body_{$k}"] = $v;
            }
        }
        ksort($params);
        $hexHash = hash("sha256", "{$timestamp}" . $this->apiSecret);
        if (count($params) > 0) {
            $s = json_encode($params, JSON_UNESCAPED_UNICODE);
            $hexHash = hash("sha256", stripcslashes($s . "{$timestamp}" . $this->apiSecret));
        }
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $apiGateway);
        $header = [
            "Content-Type:application/json",
            "X-Api-Key:{$this->apiKey}",
            "X-Signature:{$hexHash}",
            "X-Timestamp:{$timestamp}",
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $jsonStr = $body ? json_encode($body) : ''; //转换为json格式
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            if ($jsonStr) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
            }
        } elseif ($method == 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            if ($jsonStr) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
            }
        } elseif ($method == 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($jsonStr) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
            }
        } elseif ($method == 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            if ($jsonStr) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
            }
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response_data = curl_exec($ch);

        //$err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $response_data = json_decode($response_data, true);

        $response = array(
            'code' => $httpCode,
            'result' => $response_data,
        );
        return $response;
    }

    /** get timestamp
     *
     * @return float
     */
    private function getMillisecond()
    {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)));
    }


}

