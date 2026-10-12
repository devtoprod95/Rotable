#!/bin/bash
echo ""
echo "=================================="
echo "  🚀 Laravel Service list - $(date '+%Y-%m-%d %H:%M:%S')"
echo "=================================="
supervisorctl status
echo "=================================="
echo "  💡 service <name> status   서비스 상태 확인"
echo "  💡 service <name> start    서비스 시작"
echo "  💡 service <name> stop     서비스 중지"
echo "  💡 service <name> restart  서비스 재시작"
echo "=================================="
echo ""
