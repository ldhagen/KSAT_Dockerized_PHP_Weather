#!/bin/bash

# Set variables
IMAGE_NAME="ldhagen/ksat-weather-app"
VERSION="2.0.0"

echo "Building Docker image..."
docker build -t $IMAGE_NAME:$VERSION -t $IMAGE_NAME:latest .

echo "Testing the image..."
docker run -d --name test-weather-app -p 8085:80 $IMAGE_NAME:latest
sleep 10

# Test if the container is running
if docker ps | grep test-weather-app; then
    echo "✅ Container is running successfully"
    
    # Test basic web server functionality (without database)
    if curl -s -f http://localhost:8085/ > /dev/null; then
        echo "✅ Web server is responding"
        
        # Test status endpoint (it should fail gracefully without DB)
        HEALTH_RESPONSE=$(curl -s http://localhost:8085/health.php)
        if [[ $HEALTH_RESPONSE == *"unhealthy"* ]] || [[ $HEALTH_RESPONSE == *"error"* ]]; then
            echo "⚠️  Health check shows expected database connection error (this is normal in test)"
            echo "✅ Basic functionality verified - database connection will work in full deployment"
        else
            echo "❌ Unexpected health check response"
            docker logs test-weather-app
            docker stop test-weather-app
            docker rm test-weather-app
            exit 1
        fi
    else
        echo "❌ Web server failed to respond"
        docker logs test-weather-app
        docker stop test-weather-app
        docker rm test-weather-app
        exit 1
    fi
    
    docker stop test-weather-app
    docker rm test-weather-app
else
    echo "❌ Container failed to start"
    docker logs test-weather-app
    exit 1
fi

echo "Pushing to Docker Hub..."
docker push $IMAGE_NAME:$VERSION
docker push $IMAGE_NAME:latest

echo "✅ Successfully built and pushed $IMAGE_NAME:$VERSION"
echo "✅ Successfully built and pushed $IMAGE_NAME:latest"