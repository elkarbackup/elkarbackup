FROM alpine:latest
RUN apk add --no-cache openssh rsync
# Create test user and set up ssh authentication
RUN adduser -D testuser && \
    echo "testuser:testpass" | chpasswd && \
    mkdir -p /home/testuser/.ssh && \
    chown testuser:testuser /home/testuser/.ssh && \
    chmod 700 /home/testuser/.ssh && \
    sed -i 's/#PubkeyAuthentication yes/PubkeyAuthentication yes/' /etc/ssh/sshd_config && \
    sed -i 's/#AuthorizedKeysFile/AuthorizedKeysFile/' /etc/ssh/sshd_config
EXPOSE 22
RUN ssh-keygen -A
CMD ["/usr/sbin/sshd","-D"]
