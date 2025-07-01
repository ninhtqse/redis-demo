# Access container
```
docker exec -it node-1 redis-cli -p 7000 cluster nodes
```

# View master - slave
redis-cluster/node1/data/nodes.conf

| Field             | Means                                |
| ----------------- | ------------------------------------ |
| `master`          | Node là master                       |
| `slave <node_id>` | Là replica (slave) của node có ID đó |
| `0-5460`          | Slot mà master đang giữ              |
| `connected`       | Trạng thái kết nối hiện tại          |

