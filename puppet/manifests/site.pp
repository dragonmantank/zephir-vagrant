class buildtools {
    package {[
            'autoconf',
            'automake',
            'gcc',
            'git',
            'libpcre3-dev',
            'libtool',
            'make',
        ]:
        ensure => present,
        require => [Exec['apt-get update']]
    }
}

class libjson {
    require buildtools

    exec {"download libjson":
        command => 'git clone https://github.com/json-c/json-c.git',
        creates => '/usr/local/src/json-c',
        path => '/usr/bin:/bin',
        require => Package['git'],
        cwd => '/usr/local/src',
    }

    exec {"install libjson":
        command => 'sh autogen.sh && ./configure && make && make install',
        path => '/usr/bin:/bin',
        require => Exec['download libjson'],
        creates => '/usr/local/lib/libjson.so',
        cwd => '/usr/local/src/json-c',
    }
}

class basesys {
    pparepo { 'ondrej/php5-oldstable': apt_key => 'E5267A6C', dist => 'precise'}

    exec {'apt-get update':
        command => '/usr/bin/apt-get update',
        onlyif => "/bin/sh -c '[ ! -f /var/cache/apt/pkgcache.bin ] || /usr/bin/find /etc/apt/* -cnewer /var/cache/apt/pkgcache.bin | /bin/grep . > /dev/null'",
        require => Pparepo['ondrej/php5-oldstable']
    }

    package {[
            'php5',
            'php5-dev',
            're2c',
        ]:
        ensure => 'present',
        require => [Exec['apt-get update'], Class['libjson']],
    }
}

class zephir {
    require buildtools
    require libjson
    require basesys

    exec {"install zephir":
        cwd => '/vagrant',
        command => 'sh install',
        path => '/usr/bin:/bin',
    }
}

require zephir