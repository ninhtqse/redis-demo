# Redis PHP Demo

Demo các tính năng Redis sử dụng PHP, chạy bằng Docker.

## Yêu cầu
- Docker
- Docker Compose

## Cách chạy

```bash
git clone <repo này>
cd redis-cli-demo
docker-compose up --build
```

Truy cập: http://localhost:8080

## Các tính năng demo
- Kết nối Redis
- String, List, Set, Sorted Set, Hash
- TTL/Expire
- Transaction
- Pub/Sub
- Caching
- ...

## Thêm tính năng
Chỉnh sửa file `src/index.php` để bổ sung các demo khác. 


docker exec -it redis-cli-redis-node1-1 redis-cli -p 7000 cluster nodes