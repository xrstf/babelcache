# -*- coding: utf-8 -*-

import datetime

source_suffix        = '.rst'
master_doc           = 'index'
project              = u'BabelCache'
copyright            = u'%s, webvariants GbR' % (datetime.date.today().year)
version              = '2.0'
release              = '2.0'
language             = 'de'
exclude_patterns     = ['_build']
pygments_style       = 'default'
html_theme           = 'nature'
html_title           = "%s v%s Dokumentation" % (project, release)
html_use_smartypants = True
