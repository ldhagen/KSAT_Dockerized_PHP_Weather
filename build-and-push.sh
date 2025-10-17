#!/bin/bash

# Set variables
IMAGE_NAME="ldhagen/ksat-weather-app"
VERSION="2.0.0"

# Get Git commit information
GIT_COMMIT=$(git rev-parse --short HEAD)
GIT_REPO="https://github.com/ldhagen/KSAT_Dockerized_PHP_Weather"

echo "Building Docker image..."
echo "Git Commit: $GIT_COMMIT"
echo "Version: $VERSION"

# Build the image with build args
docker build \
  --build-arg GIT_COMMIT=$GIT_COMMIT \
  --build-arg GIT_REPO=$GIT_REPO \
  --build-arg VERSION=$VERSION \
  -t $IMAGE_NAME:$VERSION \
  -t $IMAGE_NAME:latest \
  -t $IMAGE_NAME:$GIT_COMMIT .

echo "Testing basic container startup..."
docker run -d --name test-weather-app -p 8085:80 $IMAGE_NAME:latest
sleep 5

if docker ps | grep test-weather-app; then
    echo "✅ Container started successfully"
    
    # Display image labels
    echo "Image Labels:"
    docker inspect $IMAGE_NAME:latest | jq -r '.[0].Config.Labels' || docker inspect $IMAGE_NAME:latest | grep -A 20 "Labels"
    
    # Test version endpoint
    echo "Version endpoint:"
    curl -s http://localhost:8085/version.php || echo "Version endpoint not accessible"
    
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
docker push $IMAGE_NAME:$GIT_COMMIT

echo "✅ Successfully built and pushed:"
echo "   - $IMAGE_NAME:$VERSION"
echo "   - $IMAGE_NAME:latest" 
echo "   - $IMAGE_NAME:$GIT_COMMIT (Git commit)"
echo ""
echo "GitHub Commit: $GIT_REPO/commit/$GIT_COMMIT"