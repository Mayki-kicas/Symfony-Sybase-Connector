#!/bin/sh
set -eu

ODBCINST_FILE="/etc/odbcinst.ini"

find_freetds_driver() {
  find /usr/lib -type f -name 'libtdsodbc.so' 2>/dev/null | head -n 1
}

register_driver() {
  name="$1"
  lib="$2"
  if grep -q "^\[$name\]" "$ODBCINST_FILE" 2>/dev/null; then
    echo "[odbc-config] ${name} already present in ${ODBCINST_FILE}." >&2
    return 0
  fi
  cat >> "$ODBCINST_FILE" <<EOC
[$name]
Description=${name} ODBC Driver
Driver=${lib}
Setup=${lib}
UsageCount=1
EOC
  echo "[odbc-config] Registered ${name} ODBC driver: ${lib}" >&2
}

FREETDS_LIB="$(find_freetds_driver || true)"
if [ -n "${FREETDS_LIB}" ]; then
  register_driver "FreeTDS" "${FREETDS_LIB}"
else
  echo "[odbc-config] FreeTDS driver not found (libtdsodbc.so)." >&2
  exit 1
fi
