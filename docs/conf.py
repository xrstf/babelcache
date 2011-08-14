# -*- coding: utf-8 -*-

import datetime

f = open('../version')
v = f.read()
f.close()

v = v.split('.')
v += ['0', '0']
[major, minor, bugfix] = v[:3]

source_suffix        = '.rst'
master_doc           = 'index'
project              = u'BabelCache'
copyright            = u'%s, webvariants GbR' % (datetime.date.today().year)
version              = major
release              = '.'.join(v[:3])
language             = 'de'
exclude_patterns     = ['_build']
pygments_style       = 'default'
html_theme           = 'nature'
html_title           = "%s v%s Dokumentation" % (project, release)
html_use_smartypants = True
