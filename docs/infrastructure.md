# Infrastructure

## Required Hardware (theoretical recommendations)

### Minimum Requirements
- **CPU**: 4 cores (2.4GHz+)
- **RAM**: 8GB
- **Storage**: 100GB SSD
- **Network**: 100Mbps bandwidth

### Recommended for Production
- **CPU**: 8-16 cores (3.0GHz+)
- **RAM**: 32-64GB
- **Storage**: 500GB+ NVMe SSD
- **Network**: 1Gbps+ bandwidth

### Considerations
- **High CPU usage** for concurrent web crawling operations
- **Significant RAM** needed for queue processing and data manipulation
- **Fast storage** required for report generation, ZIP file creation, and S3 operations
- **Good network bandwidth** essential for web crawling and data transfer
- **Database performance** critical - consider separate DB server for production


## SwitchEngines Types used in Practice

- m1.large for development/testing
- m1.xlarge or m1.xxlarge for production


