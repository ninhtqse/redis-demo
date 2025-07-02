#!/bin/sh
sleep 20

# Function to check if a node is "white" (has no key and is not in the cluster)
is_node_clean() {
  host=$1
  port=$2
  # Check if there is no key and is not in cluster
  key_count=$(redis-cli -h $host -p $port dbsize)
  cluster_info=$(redis-cli -h $host -p $port cluster info | grep 'cluster_state:ok')
  cluster_nodes=$(redis-cli -h $host -p $port cluster nodes | grep -v 'myself')
  if [ "$key_count" = "0" ] && [ -z "$cluster_info" ] && [ -z "$cluster_nodes" ]; then
    return 0
  else
    return 1
  fi
}

# Check if all nodes are "white"
all_clean=true
for i in 1 2 3 4 5 6; do
  if ! is_node_clean redis-node$i 700$((i-1)); then
    all_clean=false
    break
  fi
done

if $all_clean; then
  echo "All nodes are clean. Creating cluster..."
  echo yes | redis-cli --cluster create \
    redis-node1:7000 redis-node2:7001 redis-node3:7002 \
    redis-node4:7003 redis-node5:7004 redis-node6:7005 \
    --cluster-replicas 1
else
  echo "Cluster already exists or nodes are not clean. Skipping cluster creation."
fi