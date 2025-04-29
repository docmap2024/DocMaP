# Use Node.js 18 Alpine (lightweight Linux)
FROM node:18-alpine

# Set working directory inside container
WORKDIR /app

# Copy package.json and package-lock.json
COPY package*.json ./

# Install dependencies
RUN npm install

# Copy all project files
COPY . .

# Command to run the app
CMD ["npm", "start"]